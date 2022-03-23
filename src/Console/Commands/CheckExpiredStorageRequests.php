<?php

namespace Biigle\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Notifications\StorageRequestExpiresSoon;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Console\Command;

class CheckExpiredStorageRequests extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user-storage:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify owners of storage requests that are about to expire';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $now = now()->toImmutable();
        $weeks = config('user_storage.about_to_expire_weeks');
        $warnDate = $now->addWeeks($weeks);

        // All requests that are about to expire in the configured number of weeks.
        StorageRequest::where('expires_at', '<', $warnDate)
            ->where('expires_at', '>=', $warnDate->subDay())
            ->eachById(function ($request) {
                $request->user->notify(new StorageRequestExpiresSoon($request));
            });

        // Warn again one week before expiration (unless one week is already configured).
        if ($weeks > 1) {
            $warnDate = $now->addWeek();
            StorageRequest::where('expires_at', '<', $warnDate)
                ->where('expires_at', '>=', $warnDate->subDay())
                ->eachById(function ($request) {
                    $request->user->notify(new StorageRequestExpiresSoon($request));
                });
        }

        // Final warning one day before expiration.
        StorageRequest::where('expires_at', '<', $now->addDay())
            ->where('expires_at', '>=', $now)
            ->eachById(function ($request) {
                $request->user->notify(new StorageRequestExpiresSoon($request));
            });
    }
}
