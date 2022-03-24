<?php

namespace Biigle\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Notifications\StorageRequestExpiresSoon;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Console\Command;

class PruneStaleStorageRequests extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-storage:prune-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete storage requests that have not been submitted for a long time';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $pruneDate = now()->subWeeks(config('user_storage.delete_grace_period_weeks'));

        StorageRequest::whereNull('submitted_at')
            // Use updated_at because this will be the last time a file was uploaded
            // to the storage request (in case the full upload would actually take
            // several weeks).
            ->where('updated_at', '<', $pruneDate)
            ->eachById(function ($request) {
                $request->delete();
            });
    }
}
