<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\AssembleChunkedFile;
use Biigle\Modules\UserStorage\Notifications\StorageRequestApproved;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
use File;
use Illuminate\Support\Facades\Notification;
use Storage;
use TestCase;

class AssembleChunkedFileTest extends TestCase
{
    public function testHandle()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
            'received_chunks' => [0, 1],
            'total_chunks' => 2,
        ]);

        $disk->put($request->getPendingPath('a.jpg.0'), 'abc');
        $disk->put($request->getPendingPath('a.jpg.1'), 'def');

        $job = new AssembleChunkedFile($file);
        $job->handle();

        $this->assertFalse($disk->exists($request->getPendingPath('a.jpg.0')));
        $this->assertFalse($disk->exists($request->getPendingPath('a.jpg.1')));
        $this->assertSame('abcdef', $disk->get($request->getPendingPath('a.jpg')));

        $file->refresh();
        $this->assertNull($file->received_chunks);
        $this->assertNull($file->total_chunks);
    }

    public function testHandleNotChunked()
    {
        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);

        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $disk->put($request->getPendingPath('a.jpg'), 'abc');

        $this->expectException(Exception::class);
        $job = new AssembleChunkedFile($file);
    }
}
