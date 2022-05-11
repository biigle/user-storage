<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Storage;

class StorageRequestDirectoryControllerTest extends ApiTestCase
{
    public function testDestory()
    {
        Queue::fake();
        $request = StorageRequest::factory()->create();
        $file1 = StorageRequestFile::factory()->create([
            'path' => 'a/a.jpg',
            'storage_request_id' => $request->id,
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'b/b.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('DELETE', "/api/v1/storage-requests/{$id}/directories");

        $this->beUser();
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['a'],
            ])
            ->assertStatus(403);

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories")
            // Directories must be specified.
            ->assertStatus(422);

        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['a'],
            ])
            ->assertStatus(200);

        $this->assertNull($file1->fresh());
        $this->assertNotNull($file2->fresh());

        Queue::assertPushed(function (DeleteStorageRequestFile $job) {
            $this->assertSame('a/a.jpg', $job->path);

            return true;
        });
    }

    public function testDestoryNotExists()
    {
        $request = StorageRequest::factory()->create();
        $file1 = StorageRequestFile::factory()->create([
            'path' => 'a/a.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['b'],
            ])
            ->assertStatus(422);
    }

    public function testDestoryAllFiles()
    {
        $request = StorageRequest::factory()->create();
        $file1 = StorageRequestFile::factory()->create([
            'path' => 'a/a.jpg',
            'storage_request_id' => $request->id,
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'b/b.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['a', 'b'],
            ])
            ->assertStatus(422);
    }

    public function testDestoryActualDirectory()
    {
        $request = StorageRequest::factory()->create();
        $file1 = StorageRequestFile::factory()->create([
            'path' => 'abc/a.jpg',
            'storage_request_id' => $request->id,
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'def/b.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['a'],
            ])
            ->assertStatus(422);
    }

}
