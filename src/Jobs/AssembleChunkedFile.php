<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
use File;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class AssembleChunkedFile extends Job implements ShouldQueue
{
    use Batchable, InteractsWithQueue, SerializesModels;

    /**
     * The file to assemble.
     *
     * @var StorageRequestFile
     */
    public $file;

    /**
     * Ignore this job if the image does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param StorageRequestFile $file
     */
    public function __construct(StorageRequestFile $file)
    {
        if (is_null($file->total_chunks)) {
            throw new Exception('The file is not chunked.');
        }

        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $disk = Storage::disk(config('user_storage.pending_disk'));
        File::ensureDirectoryExists(config('user_storage.tmp_dir'));
        $filename = tempnam(config('user_storage.tmp_dir'), 'assemble-chunks-');

        try {
            $tempFile = fopen($filename, 'w+');
            $path = $this->file->request->getPendingPath($this->file->path);
            $chunkPaths = [];

            for ($i = 0; $i < $this->file->total_chunks; $i++) {
                $chunkPath = "{$path}.{$i}";
                $chunkPaths[] = $chunkPath;
                $stream = $disk->readStream($chunkPath);
                stream_copy_to_stream($stream, $tempFile);
            }

            fseek($tempFile, 0);
            $success = $disk->writeStream($path, $tempFile);
            fclose($tempFile);

            if (!$success) {
                throw new Exception("Could not store assembled file at '{$path}'.");
            }

            $success = $disk->delete($chunkPaths);

            if (!$success) {
                throw new Exception("Could not delete chunks of file '{$path}'.");
            }
        } finally {
            unlink($filename);
        }
    }
}
