<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\ApproveStorageRequest;
use Biigle\Modules\UserStorage\Notifications\StorageRequestApproved;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Support\Facades\Notification;
use Storage;
use TestCase;

class ApproveStorageRequestTest extends TestCase
{
    public function testHandle()
    {
        Notification::fake();
        config(['user_storage.storage_disk' => 'storage']);
        config(['user_storage.pending_disk' => 'pending']);
        $storageDisk = Storage::fake('storage');
        $pendingDisk = Storage::fake('pending');

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);

        $pendingDisk->put($request->getPendingPath('a.jpg'), 'abc');

        $job = new ApproveStorageRequest($request);
        $job->handle();

        $this->assertFalse($pendingDisk->exists($request->getPendingPath()));
        $this->assertTrue($storageDisk->exists($request->getStoragePath('a.jpg')));
        Notification::assertSentTo([$request->user], StorageRequestApproved::class);
    }

    public function testHandleSameDisk()
    {
        Notification::fake();
        config(['user_storage.storage_disk' => 'storage']);
        config(['user_storage.pending_disk' => 'storage']);
        $storageDisk = Storage::fake('storage');

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);

        $storageDisk->put($request->getPendingPath('a.jpg'), 'abc');

        $job = new ApproveStorageRequest($request);
        $job->handle();

        $this->assertFalse($storageDisk->exists($request->getPendingPath()));
        $this->assertTrue($storageDisk->exists($request->getStoragePath('a.jpg')));
        Notification::assertSentTo([$request->user], StorageRequestApproved::class);
    }

    public function testHandleEmpty()
    {
        Notification::fake();

        $request = StorageRequest::factory()->create();

        $job = new ApproveStorageRequest($request);
        $job->handle();

        Notification::assertNothingSent();
    }
}
