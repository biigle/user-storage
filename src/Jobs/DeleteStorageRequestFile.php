<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Storage;

class DeleteStorageRequestFile extends Job implements ShouldQueue
{
    use InteractsWithQueue, Batchable;

    /**
     * File that should be deleted
     * 
     * **/
    public $file;

    /**
     * Whether the request of the file was pending or not.
     *
     * @var bool
     */
    public $pending;

    /**
     * Directory prefix of the storage request.
     *
     * @var string
     */
    public $prefix;

    /**
     * Chunks of an uploaded file that was not assembled yet.
     *
     * @var array
     */
    public $chunks;

    /**
     * Count for retry attempts of a chunked file
     * 
     * **/
    public $oldRetryCount;

    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param StorageRequestFile $file
     */
    public function __construct(StorageRequestFile $file)
    {
        $this->file = $file;
        $this->oldRetryCount = $file->retry_count;
        $request = $file->request;
        $this->pending = is_null($request->expires_at);
        if ($this->pending) {
            $this->prefix = $request->getPendingPath();
            if ($file->received_chunks) {
                $this->chunks = $file->received_chunks;
            }
        } else {
            $this->prefix = $request->getStoragePath();
        }
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Do not delete files when delete-request is outdated
        if($this->oldRetryCount != $this->file->refresh()->retry_count) {
            return;
        }

        if ($this->pending) {
            $disk = Storage::disk(config('user_storage.pending_disk'));
        } else {
            $disk = Storage::disk(config('user_storage.storage_disk'));
        }

        $path = "{$this->prefix}/{$this->file->path}";

        if ($this->chunks) {
            $paths = array_map(function ($chunk) use ($path) {
                return "{$path}.{$chunk}";
            }, $this->chunks);

            $success = $disk->delete($paths);
        } else {
            $success = $disk->delete($path);
        }

        if (!$success) {
            throw new Exception("Could not delete file '{$this->file->path}' for storage request with prefix '{$this->prefix}'.");
        }

        if ($success && count($disk->allFiles($this->prefix)) === 0) {
            $success = $disk->deleteDirectory($this->prefix);

            if (!$success) {
                throw new Exception("Could not delete empty directory of storage request with prefix '{$this->prefix}'.");
            }
        }

        $this->file->delete();
    }
}
