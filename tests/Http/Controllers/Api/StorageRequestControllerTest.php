<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\Jobs\CleanupStorageRequest;
use Biigle\Modules\UserStorage\Jobs\ApproveStorageRequest;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Support\Facades\Bus;
use Storage;

class StorageRequestControllerTest extends ApiTestCase
{
    public function testStore()
    {
        $this->doTestApiRoute('POST', "/api/v1/storage-requests");

        $this->beGlobalGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(403);

        $this->beGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);

        $request = StorageRequest::first();
        $this->assertNotNull($request);
        $this->assertSame($this->guest()->id, $request->user_id);
        $this->assertNull($request->expires_at);
        $this->assertSame([], $request->files);
    }

    public function testStoreLimitOpenRequests()
    {
        config(['user_storage.max_pending_requests' => 2]);
        $this->beGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
        $this->postJson("/api/v1/storage-requests")->assertStatus(422);

        $request = StorageRequest::first();
        $request->update(['expires_at' => now()->addYear()]);

        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
    }

    public function testUpdate()
    {
        // This submits the request with all uploaded files.
        // Reject if no files were uploaded.
        // Set sumbitted_at to mark this.
        // Send notification to admins.
        $this->markTestIncomplete();
    }

    public function testUpdateAlreadyUpdated()
    {
        $this->markTestIncomplete();
    }

    public function testApprove()
    {
        Bus::fake();

        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
        ]);
        $id = $request->id;

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/approve");

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(403);

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(200);

        $this->assertNotNull($request->fresh()->expires_at);
        Bus::assertDispatched(function (ApproveStorageRequest $job) use ($request) {
            return $job->request->id === $request->id;
        });
    }

    public function testApproveEmpty()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(422);
    }

    public function testApproveAlreadyApproveed()
    {
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
            'expires_at' => '2022-03-11 11:22:00',
        ]);
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(404);
    }

    public function testReject()
    {
        // Send notification with reason.
        $this->markTestIncomplete();
    }

    public function testRejectAlreadyApproveed()
    {
        $this->markTestIncomplete();
    }

    public function testDestroy()
    {
        config(['user_storage.storage_disk' => 'storage']);
        config(['user_storage.pending_disk' => 'pending']);
        $disk = Storage::fake('storage');
        Bus::fake();

        $request = StorageRequest::factory()->create([
            'files' => ['dir/test.jpg'],
            'expires_at' => '2022-03-10 15:28:00',
        ]);
        $id = $request->id;

        $disk->put("user-{$request->user->id}/dir/test.jpg", 'abc');

        $this->doTestApiRoute('DELETE', "/api/v1/storage-requests/{$id}");

        $this->beUser();
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(403);

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertDispatched(function (CleanupStorageRequest $job) use ($request) {
            return count($job->files) === 1 && $job->files[0] === "dir/test.jpg" && $job->user->id = $request->user_id;
        });
        $this->assertNull($request->fresh());
    }

    public function testDestroyPending()
    {
        config(['user_storage.storage_disk' => 'storage']);
        config(['user_storage.pending_disk' => 'pending']);
        $disk = Storage::fake('storage');
        Bus::fake();

        $request = StorageRequest::factory()->create([
            'files' => ['dir/test.jpg'],
        ]);
        $id = $request->id;

        $disk->put("user-{$request->user->id}/dir/test.jpg", 'abc');

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertDispatched(function (CleanupStorageRequest $job) use ($request) {
            return count($job->files) === 1 && $job->files[0] === "dir/test.jpg" && $job->user->id = $request->user_id;
        });
        $this->assertNull($request->fresh());
    }

    public function testDestroyEmpty()
    {
        Bus::fake();

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertNothingDispatched();
        $this->assertNull($request->fresh());
    }

}