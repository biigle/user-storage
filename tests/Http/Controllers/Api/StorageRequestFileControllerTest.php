<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\UploadedFile;
use Storage;

class StorageRequestFileControllerTest extends ApiTestCase
{
    public function testStore()
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

    public function testStorePrefix()
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

    public function testStoreTooLarge()
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

    public function testStoreMimeType()
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

    public function testStoreRequestSubmitted()
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

    public function testStoreExistsInRequestsDisk()
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

    public function testStoreExistsInUserDisk()
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

    public function testStoreExceedsQuota()
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

    public function testStoreExceedsConfigQuotaButNotUserQuota()
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

    public function testDestory()
    {
        // Maybe use the cleanup job?
        $this->markTestIncomplete();
    }

    public function testDestoryPending()
    {
        $this->markTestIncomplete();
    }

}
