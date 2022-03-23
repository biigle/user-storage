<?php

namespace Biigle\Tests\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\Notifications\StorageRequestExpiresSoon;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Support\Facades\Notification;
use TestCase;

class CheckExpiredStorageRequestsTest extends TestCase
{
    public function testHandle()
    {
        config(['user_storage.about_to_expire_weeks' => 2]);
        Notification::fake();
        $request1 = StorageRequest::factory()->create([
            'expires_at' => now()->addWeeks(2)->subHour(),
        ]);
        $request2 = StorageRequest::factory()->create([
            'expires_at' => now()->addWeek()->subHour(),
        ]);
        $request3 = StorageRequest::factory()->create([
            'expires_at' => now()->addDay()->subHour(),
        ]);
        $request4 = StorageRequest::factory()->create([
            'expires_at' => now()->addDays(2),
        ]);
        $request5 = StorageRequest::factory()->create([
            'expires_at' => now()->addWeeks(4),
        ]);
        $request6 = StorageRequest::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('user-storage:check')->assertExitCode(0);

        Notification::assertSentTo([$request1->user], StorageRequestExpiresSoon::class);
        Notification::assertSentTo([$request2->user], StorageRequestExpiresSoon::class);
        Notification::assertSentTo([$request3->user], StorageRequestExpiresSoon::class);

        Notification::assertNotSentTo([$request4->user], StorageRequestExpiresSoon::class);
        Notification::assertNotSentTo([$request5->user], StorageRequestExpiresSoon::class);
        Notification::assertNotSentTo([$request6->user], StorageRequestExpiresSoon::class);
    }
}
