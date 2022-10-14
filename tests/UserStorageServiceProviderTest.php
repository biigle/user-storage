<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\Support\FilesystemManager;
use Biigle\Role;
use Biigle\User;
use Illuminate\Support\Facades\Gate;
use Storage;
use TestCase;

class UserStorageServiceProviderTest extends TestCase
{
    public function testOverrideStorageFacade()
    {
        $this->assertInstanceOf(FilesystemManager::class, Storage::getFacadeRoot());
    }

    public function testOverrideUseDiskGate()
    {
        $user = User::factory()->create();
        $otherId = $user->id + 1;
        $this->be($user);
        $this->assertTrue(Gate::allows('use-disk', "user-{$user->id}"));
        $this->assertFalse(Gate::allows('use-disk', "user-{$otherId}"));
    }

    public function testOverrideUseDiskGateGlobalAdmin()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create([
            'role_id' => Role::adminId(),
        ]);
        $this->be($admin);
        $this->assertTrue(Gate::allows('use-disk', "user-{$user->id}"));
    }
}
