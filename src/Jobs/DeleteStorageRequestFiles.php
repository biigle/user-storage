<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Biigle\User as BaseUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Flysystem\UnableToRetrieveMetadata;
use Storage;

class DeleteStorageRequestFiles extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The user who created the storage request.
     *
     * @var BaseUser
     */
    public $user;

    /**
     * Files belonging to the storage request.
     *
     * @var array
     */
    public $files;

    /**
     * Whether the request was pending or not.
     *
     * @var bool
     */
    public $pending;

    /**
     * Directory prefix to use for each file.
     *
     * @var string
     */
    public $prefix;

    /**
     * Create a new job instance.
     *
     * @param StorageRequest $request
     * @param array $only Delete only this subset of files of the request
     */
    public function __construct(StorageRequest $request, array $only = [])
    {
        $this->user = $request->user;
        $this->files = $only ?: $request->files;
        $this->deleteAllFiles = empty($only);
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

        $files = array_map(fn ($f) => "{$this->prefix}/{$f}", $this->files);

        $totalSize = 0;
        if (!is_null($this->user)) {
            foreach ($files as $path) {
                try {
                    $totalSize += $disk->size($path);
                } catch (UnableToRetrieveMetadata $e) {
                    // The file probably does not exist.
                    continue;
                }
            }
        }

        if ($this->pending && $this->deleteAllFiles) {
            $disk->deleteDirectory($this->prefix);
        } else {
            $disk->delete($files);
            if (count($disk->files($this->prefix)) === 0) {
                $disk->deleteDirectory($this->prefix);
            }
        }

        if (!is_null($this->user)) {
            $user = User::convert($this->user);
            $user->storage_quota_used -= $totalSize;
            $user->save();
        }
    }
}
