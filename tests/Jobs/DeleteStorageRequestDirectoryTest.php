<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestDirectory;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Storage;
use TestCase;

class DeleteStorageRequestDirectoryTest extends TestCase
{
    public function testHandle()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-10 15:46:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $job = new DeleteStorageRequestDirectory($request);
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}"));
    }

    public function testHandlePending()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
        ]);

        $disk->put("request-{$file->storage_request_id}/a.jpg", 'abc');

        $job = new DeleteStorageRequestDirectory($file->request);
        $job->handle();

        $this->assertFalse($disk->exists("request-{$file->storage_request_id}"));
    }
}
