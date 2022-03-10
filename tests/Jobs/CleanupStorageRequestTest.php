<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\CleanupStorageRequest;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Storage;
use TestCase;

class CleanupStorageRequestTest extends TestCase
{
    public function testHandle()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg', 'b.jpg'],
            'expires_at' => '2022-03-10 15:46:00',
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');
        $disk->put("user-{$request->user_id}/c.jpg", 'abc');
        $user = User::convert($request->user);
        $user->storage_quota_used = 10;
        $request->user = $user;

        $job = new CleanupStorageRequest($request);
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}/a.jpg"));
        $this->assertTrue($disk->exists("user-{$request->user_id}/c.jpg"));
        $this->assertSame(7, $user->fresh()->storage_quota_used);
    }

    public function testHandleClearAll()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
            'expires_at' => '2022-03-10 15:46:00',
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $job = new CleanupStorageRequest($request);
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}"));
    }

    public function testHandlePending()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
        ]);

        $disk->put("request-{$request->id}/a.jpg", 'abc');

        $job = new CleanupStorageRequest($request);
        $job->handle();

        $this->assertFalse($disk->exists("request-{$request->id}"));
    }

    public function testHandleUserDeleted()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
            'expires_at' => '2022-03-10 15:46:00',
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $job = new CleanupStorageRequest($request);
        $job->user = null;
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}/a.jpg"));
    }
}
