<?php

namespace Biigle\Modules\UserStorage\Jobs;

use Biigle\Jobs\Job;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
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

        // Assemble chunks in a config('user_storage.tmp_dir')
    }
}
