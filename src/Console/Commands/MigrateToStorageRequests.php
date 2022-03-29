<?php

namespace Biigle\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Notifications\StorageRequestExpiresSoon;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Console\Command;
use Storage;

class MigrateToStorageRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-storage:migrate
        {id : ID of the user to migrate storage for}
        {--dry-run : Don\'t create actual storage requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing user files to storage requests';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::findOrFail($this->argument('id'));

        if (StorageRequest::where('user_id', $user->id)->exists()) {
            $this->error('The user already has existing storage requests!');

            return 1;
        }

        $disk = Storage::disk(config('user_storage.storage_disk'));
        $prefix = "user-{$user->id}";
        $files = $disk->allFiles($prefix);

        if (empty($files)) {
            $this->line('No files found.');

            return 0;
        }

        $totalSize = array_reduce($files, function ($carry, $file) use ($disk) {
            return $carry + $disk->size($file);
        }, 0);

        $files = array_map(function ($path) use ($prefix) {
            return substr($path, strlen($prefix) + 1);
        }, $files);

        $request = StorageRequest::make([
            'user_id' => $user->id,
            'submitted_at' => now(),
            'expires_at' => now()->addMonths(config('user_storage.expires_months')),
            'files' => $files,
        ]);

        $user->storage_quota_used += $totalSize;

        if (!$this->option('dry-run')) {
            $request->save();
            $user->save();
        }

        $humanSize = size_for_humans($totalSize);

        $this->info("Migrated {$request->files_count} file(s) ({$humanSize}).");

        return 0;
    }
}
