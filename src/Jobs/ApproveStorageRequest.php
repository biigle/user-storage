<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\Notifications\StorageRequestApproved;
use Biigle\Modules\UserStorage\StorageRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class ApproveStorageRequest extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The storage request to confirm
     *
     * @var StorageRequest
     */
    public $request;

    /**
     * Ignore this job if the image does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param StorageRequest $request
     */
    public function __construct(StorageRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (!$this->request->files()->exists()) {
            return;
        }

        $storageDisk = Storage::disk(config('user_storage.storage_disk'));
        $pendingDisk = Storage::disk(config('user_storage.pending_disk'));
        $useCopy = false;

        if ($storageDisk === $pendingDisk) {
            $useCopy = true;
        }

        $paths = $this->request->files()->pluck('path');
        foreach ($paths as $path) {
            if ($useCopy) {
                $success = $storageDisk->copy(
                    $this->request->getPendingPath($path),
                    $this->request->getStoragePath($path)
                );
            } else {
                $stream = $pendingDisk->readStream($this->request->getPendingPath($path));
                $success = $storageDisk->writeStream($this->request->getStoragePath($path), $stream);
            }

            if (!$success) {
                throw new Exception("Could not copy file '{$path}' of storage request {$this->request->id}");
            }
        }

        // Notify user before deleting old directory because they can already use the
        // files. If deleting goes wrong below, it's only of concern for the instance
        // admins.
        $this->request->user->notify(new StorageRequestApproved($this->request));

        $success = $pendingDisk->deleteDirectory($this->request->getPendingPath());
        if (!$success) {
            throw new Exception("Could not delete pending files of storage request {$this->request->id}");
        }
    }
}
