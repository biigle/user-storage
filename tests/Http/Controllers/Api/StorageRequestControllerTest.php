<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Tests\Modules\UserStorage\StorageRequestTest;

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

    public function testStoreFile()
    {
        // This should update the used quota.
        // Perform MIME type check.
        // Perform file size check (add PHP ini config instructions to readme).
        $this->markTestIncomplete();
    }

    public function testStoreFileExistsInRequestsDisk()
    {
        $this->markTestIncomplete();
    }

    public function testStoreFileExistsInUserDisk()
    {
        $this->markTestIncomplete();
    }

    public function testStoreFileExceedsQuota()
    {
        $this->markTestIncomplete();
    }

    public function testStoreFileExceedsConfigQuotaButNotUserQuota()
    {
        $this->markTestIncomplete();
    }

    public function testUpdate()
    {
        // This submits the request with all uploaded files.
        // Reject if no files were uploaded.
        $this->markTestIncomplete();
    }

    public function testConfirm()
    {
        // Submit a queued job that moves the files and then notifies the user who
        // created the request.
        $this->markTestIncomplete();
    }

    public function testDestroy()
    {
        // Submit a job that deletes the files on the requests disk and the user storage
        // disk.
        $this->markTestIncomplete();
    }
}
