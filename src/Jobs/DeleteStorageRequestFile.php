<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Storage;

class DeleteStorageRequestFile extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * File path to be deleted.
     *
     * @var string
     */
    public $path;

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
     * Create a new job instance.
     *
     * @param StorageRequestFile $file
     */
    public function __construct(StorageRequestFile $file)
    {
        $this->path = $file->path;
        $request = $file->request;
        $this->pending = is_null($request->expires_at);
        $this->prefix = $this->pending ? $request->getPendingPath() : $request->getStoragePath();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->pending) {
            $disk = Storage::disk(config('user_storage.pending_disk'));
        } else {
            $disk = Storage::disk(config('user_storage.storage_disk'));
        }

        $success = $disk->delete("{$this->prefix}/{$this->path}");

        if (!$success) {
            throw new Exception("Could not delete file '{$this->path}' for storage request with prefix '{$this->prefix}'.");
        }

        if ($success && count($disk->allFiles($this->prefix)) === 0) {
            $success = $disk->deleteDirectory($this->prefix);

            if (!$success) {
                throw new Exception("Could not delete empty directory of storage request with prefix '{$this->prefix}'.");
            }
        }
    }
}
