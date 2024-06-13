<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\Support\ChunkedFileStreamPump;
use Exception;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\StreamWrapper;
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

        $pump = new ChunkedFileStreamPump($this->file);
        $stream = new PumpStream($pump, ['size' => $this->file->size]);
        $resource = StreamWrapper::getResource($stream);

        $disk = Storage::disk(config('user_storage.pending_disk'));

        $path = $this->file->request->getPendingPath($this->file->path);
        $success = $disk->writeStream($path, $resource);

        if (!$success) {
            throw new Exception("Could not store assembled file at '{$path}'.");
        }

        $chunkPaths = array_map(fn ($i) => "{$path}.{$i}", $this->file->received_chunks);
        $success = $disk->delete($chunkPaths);

        if (!$success) {
            throw new Exception("Could not delete chunks of file '{$path}'.");
        }

        $this->file->update([
            'received_chunks' => null,
            'total_chunks' => null,
        ]);
    }
}
