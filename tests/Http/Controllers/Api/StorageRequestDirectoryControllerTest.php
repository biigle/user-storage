<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Storage;

class StorageRequestDirectoryControllerTest extends ApiTestCase
{
    public function testDestory()
    {
        Bus::fake();
        $request = StorageRequest::factory()->create([
            'files' => ['a/a.jpg', 'b/b.jpg'],
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

        $this->assertSame(['b/b.jpg'], $request->fresh()->files);

        Bus::assertDispatched(function (DeleteStorageRequestFiles $job) use ($request) {
            $this->assertCount(1, $job->files);
            $this->assertSame('a/a.jpg', $job->files[0]);

            return true;
        });
    }

    public function testDestoryNotExists()
    {
        $request = StorageRequest::factory()->create([
            'files' => ['a/a.jpg'],
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
        $request = StorageRequest::factory()->create([
            'files' => ['a/a.jpg', 'b/b.jpg'],
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
        $request = StorageRequest::factory()->create([
            'files' => ['abc/a.jpg', 'def/b.jpg'],
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}/directories", [
                'directories' => ['a'],
            ])
            ->assertStatus(422);
    }

}
