<?php

namespace Biigle\Tests\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use TestCase;

class PruneExpiredStorageRequestsTest extends TestCase
{
    public function testHandle()
    {
        Bus::fake();

        $request1 = StorageRequest::factory()->create([
            'expires_at' => now()->subWeeks(2),
        ]);
        $file1 = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request1->id,
        ]);
        $request2 = StorageRequest::factory()->create([
            'expires_at' => now()->subDay(),
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'b.jpg',
            'storage_request_id' => $request2->id,
        ]);
        $request3 = StorageRequest::factory()->create([
            'expires_at' => now()->addDay(),
        ]);
        $file3 = StorageRequestFile::factory()->create([
            'path' => 'c.jpg',
            'storage_request_id' => $request3->id,
        ]);

        $this->artisan('user-storage:prune-expired')->assertExitCode(0);

        Bus::assertBatched(function (PendingBatch $batch) use ($file1) {
            $this->assertEquals(1, $batch->jobs->count());
            $this->assertEquals($file1->path, $batch->jobs->first()->file->path);
            return true;
        });

        $this->assertModelMissing($request1);
        $this->assertModelMissing($file1);
        $this->assertModelExists($request2);
        $this->assertModelExists($file2);
        $this->assertModelExists($request3);
        $this->assertModelExists($file3);

    }
}
