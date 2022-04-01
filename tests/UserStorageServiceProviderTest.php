<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\Support\FilesystemManager;
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
}
