<?php

namespace Biigle\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Notifications\StorageRequestExpiresSoon;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Console\Command;

class PruneExpiredStorageRequests extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-storage:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired storage requests (after the grace period)';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $pruneDate = now()->subWeeks(config('user_storage.delete_grace_period_weeks'));

        StorageRequest::where('expires_at', '<', $pruneDate)
            ->eachById(function ($request) {
                $request->delete();
            });
    }
}
