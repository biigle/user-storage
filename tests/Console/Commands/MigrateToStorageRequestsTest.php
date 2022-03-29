<?php

namespace Biigle\Tests\Modules\UserStorage\Console\Commands;

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Biigle\User as BaseUser;
use Storage;
use TestCase;

class MigrateToStorageRequestsTest extends TestCase
{
    public function testHandle()
    {
        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');

        $user = BaseUser::factory()->create();

        $disk->put("user-{$user->id}/dir/file.jpg", 'abc');

        $this->assertSame(0, StorageRequest::count());

        $this->artisan('user-storage:migrate', ['id' => $user->id])->assertExitCode(0);

        $request = StorageRequest::first();
        $this->assertNotNull($request);
        $this->assertNotNull($request->created_at);
        $this->assertNotNull($request->updated_at);
        $this->assertNotNull($request->submitted_at);
        $this->assertNotNull($request->expires_at);
        $this->assertSame(['dir/file.jpg'], $request->files);

        $user = User::convert($user->refresh());
        $this->assertSame(3, $user->storage_quota_used);
    }

    public function testHandleOnlyWithoutExistingRequests()
    {
        $user = BaseUser::factory()->create();
        StorageRequest::factory()->create(['user_id' => $user->id]);
        $this->artisan('user-storage:migrate', ['id' => $user->id])->assertExitCode(1);
    }
}
