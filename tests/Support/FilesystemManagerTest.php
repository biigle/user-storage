<?php

namespace Biigle\Tests\Modules\UserStorage\Support;

use Biigle\Modules\UserStorage\Support\FilesystemManager;
use Illuminate\Filesystem\Filesystem;
use Storage;
use TestCase;

class FilesystemManagerTest extends TestCase
{
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

        $manager = new FilesystemManager(app());
        $userDisk = $manager->disk('user-123');
        $this->assertSame('abc', $userDisk->get('a/b.jpg'));
    }

    public function testAppendUrlSuffix()
    {
        config(['user_storage.storage_disk' => 'test']);
        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'url' => 'http://example.com/storage',
        ]]);

        $manager = new FilesystemManager(app());
        $userDisk = $manager->disk('user-123');
        $this->assertSame('http://example.com/storage/user-123/a/b.jpg', $userDisk->url('a/b.jpg'));
    }
}
