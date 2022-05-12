<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Storage;

class DeleteStorageRequestDirectory extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Directory path to be deleted.
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
     * Create a new job instance.
     *
     * @param StorageRequest $file
     */
    public function __construct(StorageRequest $request)
    {
        $this->pending = is_null($request->expires_at);
        $this->path = $this->pending ? $request->getPendingPath() : $request->getStoragePath();
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

        $success = $disk->deleteDirectory($this->path);

        if (!$success) {
            throw new Exception("Could not delete storage request directory '{$this->path}'.");
        }
    }
}
