<?php

namespace Biigle\Tests\Modules\UserStorage\Support;

use Biigle\Modules\UserStorage\Support\ChunkedFileStreamPump;
use Biigle\Modules\UserStorage\Notifications\StorageRequestApproved;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Exception;
use File;
use Illuminate\Support\Facades\Notification;
use Storage;
use TestCase;

class ChunkedFileStreamPumpTest extends TestCase
{
    public function testInvoke()
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

        $disk->put($request->getPendingPath('a.jpg.0'), 'abcc');
        $disk->put($request->getPendingPath('a.jpg.1'), 'def');

        $pump = new ChunkedFileStreamPump($file);

        $this->assertEquals('ab', $pump(2));
        $this->assertEquals('cc', $pump(2));
        $this->assertEquals('de', $pump(2));
        $this->assertEquals('f', $pump(2));
        $this->assertFalse($pump(2));
    }
}
