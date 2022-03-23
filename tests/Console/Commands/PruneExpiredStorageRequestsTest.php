<?php

namespace Biigle\Tests\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Support\Facades\Bus;
use TestCase;

class PruneExpiredStorageRequestsTest extends TestCase
{
    public function testHandle()
    {
        Bus::fake();

        $request1 = StorageRequest::factory()->create([
            'files' => ['dir/test.jpg'],
            'expires_at' => now()->subWeeks(2),
        ]);
        $request2 = StorageRequest::factory()->create([
            'expires_at' => now()->subDay(),
        ]);
        $request3 = StorageRequest::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('user-storage:prune')->assertExitCode(0);

        Bus::assertDispatched(function (DeleteStorageRequestFiles $job) use ($request1) {
            return count($job->files) === 1 && $job->files[0] === "dir/test.jpg" && $job->user->id === $request1->user_id;
        });
        Bus::assertNotDispatched(function (DeleteStorageRequestFiles $job) use ($request2) {
            return $job->user->id === $request2->user_id;
        });
        Bus::assertNotDispatched(function (DeleteStorageRequestFiles $job) use ($request3) {
            return $job->user->id === $request3->user_id;
        });
        $this->assertModelMissing($request1);
        $this->assertModelExists($request2);
        $this->assertModelExists($request3);

    }
}
