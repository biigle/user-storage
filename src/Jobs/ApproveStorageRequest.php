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
        if (empty($this->request->files)) {
            return;
        }

        $storageDisk = Storage::disk(config('user_storage.storage_disk'));
        $pendingDisk = Storage::disk(config('user_storage.pending_disk'));
        $useCopy = false;

        if ($storageDisk === $pendingDisk) {
            $useCopy = true;
        }

        foreach ($this->request->files as $file) {
            if ($useCopy) {
                $success = $storageDisk->copy(
                    $this->request->getPendingPath($file),
                    $this->request->getStoragePath($file)
                );
            } else {
                $stream = $pendingDisk->readStream($this->request->getPendingPath($file));
                $success = $storageDisk->writeStream($this->request->getStoragePath($file), $stream);
            }

            if (!$success) {
                throw new Exception("Could not copy file '{$file}' of storage request {$this->request->id}");
            }
        }
        $success = $pendingDisk->deleteDirectory($this->request->getPendingPath());
        if (!$success) {
            throw new Exception("Could not delete pending files of storage request {$this->request->id}");
        }
        $this->request->user->notify(new StorageRequestApproved($this->request));
    }
}
