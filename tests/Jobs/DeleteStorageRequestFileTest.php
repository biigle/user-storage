<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\User;
use Storage;
use TestCase;

class DeleteStorageRequestFileTest extends TestCase
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
            'retry_count' => 1,
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $job = new DeleteStorageRequestFile($file);
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}/a.jpg"));
        $this->assertModelMissing($file);
    }

    public function testHandleClearAll()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-10 15:46:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
            'retry_count' => 1,
        ]);

        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $job = new DeleteStorageRequestFile($file);
        $job->handle();

        $this->assertFalse($disk->exists("user-{$request->user_id}"));
        $this->assertModelMissing($file);
    }

    public function testHandlePending()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'retry_count' => 1,
        ]);

        $disk->put("request-{$file->storage_request_id}/a.jpg", 'abc');

        $job = new DeleteStorageRequestFile($file);
        $job->handle();

        $this->assertFalse($disk->exists("request-{$file->storage_request_id}/a.jpg"));
        $this->assertModelMissing($file);
    }

    public function testHandleChunks()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'received_chunks' => [0, 2],
            'total_chunks' => 3,
            'retry_count' => 1,
        ]);

        $disk->put("request-{$file->storage_request_id}/a.jpg.0", 'abc');
        $disk->put("request-{$file->storage_request_id}/a.jpg.2", 'abc');

        $job = new DeleteStorageRequestFile($file);
        $job->handle();

        $this->assertFalse($disk->exists("request-{$file->storage_request_id}/a.jpg.0"));
        $this->assertFalse($disk->exists("request-{$file->storage_request_id}/a.jpg.2"));
        $this->assertModelMissing($file);
    }

    public function testOutdatedDeleteJob(){
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'received_chunks' => [0, 2],
            'total_chunks' => 3,
            'retry_count' => 1,
        ]);

        $disk->put("request-{$file->storage_request_id}/a.jpg.0", 'abc');
        $disk->put("request-{$file->storage_request_id}/a.jpg.2", 'abc');

        $job = new DeleteStorageRequestFile($file);

        $file->retry_count += 1;
        $file->save();

        $job->handle();

        $this->assertTrue($disk->exists("request-{$file->storage_request_id}/a.jpg.0"));
        $this->assertTrue($disk->exists("request-{$file->storage_request_id}/a.jpg.2"));
        $this->assertModelExists($file);
    }
}
