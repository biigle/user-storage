<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Role;
use Biigle\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Storage;
use TestCase;

class UserStorageServiceProviderTest extends TestCase
{
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

    public function testResolveUserStorageDisk()
    {
        $root = storage_path('framework/testing/disks/test');
        (new Filesystem)->cleanDirectory($root);

        config(['user_storage.storage_disk' => 'test']);
        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => $root,
        ]]);

        $disk = Storage::disk('test');
        $disk->put('user-123/a/b.jpg', 'abc');

        $userDisk = Storage::disk('user-123');
        $this->assertSame('abc', $userDisk->get('a/b.jpg'));
    }

    public function testUserStorageDiskAppendUrlSuffix()
    {
        $root = storage_path('framework/testing/disks/test');
        (new Filesystem)->cleanDirectory($root);

        config(['user_storage.storage_disk' => 'test']);
        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => $root,
            'url' => 'http://example.com/storage',
        ]]);

        $userDisk = Storage::disk('user-123');
        $this->assertSame('http://example.com/storage/user-123/a/b.jpg', $userDisk->url('a/b.jpg'));
    }
}
