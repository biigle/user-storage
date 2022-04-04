<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Mockery;
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
        $this->postJson("/api/v1/storage-requests/{$id}/files")->assertStatus(422);

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

    public function testStoreTwo()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);

        $request->refresh();
        $this->assertSame(['test.jpg', 'test2.jpg'], $request->files);
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

    public function testStoreExistsInSameRequest()
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

    public function testStoreExistsInOtherRequest()
    {
        $request = StorageRequest::factory()->create();

        StorageRequest::factory()->create([
            'user_id' => $request->user_id,
            'files' => ['test.jpg'],
        ]);
        StorageRequest::factory()->create([
            'user_id' => $request->user_id,
            'files' => ['test2.jpg', 'test3.jpg'],
        ]);

        $id = $request->id;

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

    public function testStoreMaintenanceMode()
    {
        config(['user_storage.maintenance_mode' => true]);
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(403);
    }

    public function testShow() {
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg', 'b.jpg'],
        ]);
        $id = $request->id;

        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $disk->put("request-{$id}/a.jpg", 'abc');
        $disk->put("request-{$id}/c.jpg", 'abc');

        $this->doTestApiRoute('GET', "/api/v1/storage-requests/{$id}/files?path=a.jpg");

        $this->beUser();
        $this->get("/api/v1/storage-requests/{$id}/files?path=a.jpg")->assertStatus(404);

        $this->be($request->user);
        $this->get("/api/v1/storage-requests/{$id}/files?path=a.jpg")->assertStatus(404);

        $this->beGlobalAdmin();
        $this->get("/api/v1/storage-requests/{$id}/files?path=a.jpg")->assertStatus(200);
        $this->get("/api/v1/storage-requests/{$id}/files?path=b.jpg")->assertStatus(404);
        $this->get("/api/v1/storage-requests/{$id}/files?path=c.jpg")->assertStatus(404);
    }

    public function testShowApproved() {
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
            'expires_at' => '2022-03-28 14:03:00',
        ]);
        $id = $request->id;

        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $this->beGlobalAdmin();
        $this->get("/api/v1/storage-requests/{$id}/files?path=a.jpg")->assertStatus(200);
    }

    public function testShowPublic() {
        $mock = Mockery::mock();
        $mock->shouldReceive('temporaryUrl')->once()->andReturn('myurl');
        Storage::shouldReceive('disk')->andReturn($mock);

        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
        ]);
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->get("/api/v1/storage-requests/{$id}/files?path=a.jpg")
            ->assertRedirect('myurl');
    }

    public function testShowUrlEncode() {
        $request = StorageRequest::factory()->create([
            'files' => ['my dir/a.jpg'],
        ]);
        $id = $request->id;

        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $disk->put("request-{$id}/my dir/a.jpg", 'abc');

        $this->beGlobalAdmin();
        $this->get("/api/v1/storage-requests/{$id}/files?path=my%20dir%2Fa.jpg")
            ->assertStatus(200);
    }

    public function testDestory()
    {
        Bus::fake();
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg', 'b.jpg'],
        ]);
        $id = $request->id;

        $this->doTestApiRoute('DELETE', "/api/v1/storage-requests/{$id}/files");

        $this->beUser();
        $this->deleteJson("/api/v1/storage-requests/{$id}/files", [
                'files' => ['a.jpg'],
            ])
            ->assertStatus(403);

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/files")
            // Files must be specified.
            ->assertStatus(422);

        $this->deleteJson("/api/v1/storage-requests/{$id}/files", [
                'files' => ['a.jpg'],
            ])
            ->assertStatus(200);

        $this->assertSame(['b.jpg'], $request->fresh()->files);

        Bus::assertDispatched(function (DeleteStorageRequestFiles $job) use ($request) {
            $this->assertCount(1, $job->files);
            $this->assertSame('a.jpg', $job->files[0]);

            return true;
        });
    }

    public function testDestoryNotExists()
    {
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg'],
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/files", [
                'files' => ['b.jpg'],
            ])
            ->assertStatus(422);
    }

    public function testDestoryAllFiles()
    {
        $request = StorageRequest::factory()->create([
            'files' => ['a.jpg', 'b.jpg'],
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/files", [
                'files' => ['a.jpg', 'b.jpg'],
            ])
            ->assertStatus(422);
    }

}
