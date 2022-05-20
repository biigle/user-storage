<?php

namespace Biigle\Tests\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestDirectory;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Support\Facades\Bus;
use TestCase;

class PruneStaleStorageRequestsTest extends TestCase
{
    public function testHandle()
    {
        Bus::fake();

        $request1 = StorageRequest::factory()->create([
            'updated_at' => now()->subWeeks(2),
        ]);
        $file1 = StorageRequestFile::factory()->create([
            'storage_request_id' => $request1->id,
            'path' => 'dir/test.jpg',
        ]);
        $request2 = StorageRequest::factory()->create([
            'updated_at' => now()->subDay(),
        ]);
        $request3 = StorageRequest::factory()->create([
            'updated_at' => now()->subWeeks(2),
            'submitted_at' => now()->subWeeks(1),
        ]);

        $this->artisan('user-storage:prune-stale')->assertExitCode(0);

        Bus::assertDispatched(function (DeleteStorageRequestDirectory $job) use ($request1) {
            return $job->path === $request1->getPendingPath();
        });
        Bus::assertNotDispatched(function (DeleteStorageRequestDirectory $job) use ($request2) {
            return $job->path === $request2->getPendingPath();
        });
        Bus::assertNotDispatched(function (DeleteStorageRequestDirectory $job) use ($request3) {
            return $job->path === $request3->getPendingPath();
        });
        $this->assertModelMissing($request1);
        $this->assertModelMissing($file1);
        $this->assertModelExists($request2);
        $this->assertModelExists($request3);

    }
}
