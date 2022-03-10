<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Biigle\Tests\Modules\UserStorage\StorageRequestTest;
use Illuminate\Http\UploadedFile;
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

    public function testStoreFile()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/files");

        $this->beUser();
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(403);

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files")
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => 'abc'])
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);

        $this->assertTrue($disk->exists("request-{$id}/test.jpg"));
        $request->refresh();
        $this->assertSame(['test.jpg'], $request->files);
        $user = User::convert($request->user);
        $this->assertSame(44074, $user->storage_quota_used);
    }

    public function testStoreFilePrefix()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc/def',
                'file' => $file,
            ])
            ->assertStatus(200);

        $this->assertTrue($disk->exists("request-{$id}/abc/def/test.jpg"));
        $this->assertSame(['abc/def/test.jpg'], $request->fresh()->files);
    }

    public function testStoreFileTooLarge()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 10000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreFileMimeType()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.txt", 'test.txt', 'text/plain', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);

        // Attempt to spoof MIME type.
        $file = new UploadedFile(__DIR__."/../../../files/test.txt", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreFileRequestSubmitted()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create([
            'submitted_at' => '2022-03-10 10:55:00',
        ]);
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreFileExistsInRequestsDisk()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $disk->put("request-{$id}/test.jpg", 'abc');

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);

        $this->assertNotSame('abc', $disk->get("request-{$id}/test.jpg"));
    }

    public function testStoreFileExistsInUserDisk()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $disk->put("user-{$request->user->id}/test.jpg", 'abc');

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreFileExceedsQuota()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $disk->put("user-{$request->user->id}/test.jpg", 'abc');

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreFileExceedsConfigQuotaButNotUserQuota()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $user = User::convert($request->user);
        $user->storage_quota_available = 100000;
        $user->save();
        $request->user->refresh();

        $disk->put("user-{$request->user->id}/test.jpg", 'abc');

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);
    }

    public function testUpdate()
    {
        // This submits the request with all uploaded files.
        // Reject if no files were uploaded.
        // Set sumbitted_at to mark this.
        $this->markTestIncomplete();
    }

    public function testConfirm()
    {
        // Submit a queued job that moves the files and then notifies the user who
        // created the request.
        // Set expires_at to mark that the request is confirmed.
        $this->markTestIncomplete();
    }

    public function testDestroy()
    {
        // Submit a job that deletes the files on the requests disk and the user storage
        // disk.
        $this->markTestIncomplete();
    }
}
