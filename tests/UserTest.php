<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\User;
use Biigle\User as BaseUser;
use Cache;
use TestCase;

class UserTest extends TestCase
{
    public function testConvert()
    {
        $base = BaseUser::factory()->make();
        $user = User::convert($base);
        $this->assertInstanceOf(User::class, $user);
    }

    public function testGetSetStorageQuotaAvailable()
    {
        config(['user_storage.user_quota' => 100]);
        $user = User::convert(BaseUser::factory()->make());

        $this->assertSame(100, $user->storage_quota_available);

        $user->storage_quota_available = 1000;

        $this->assertSame(1000, $user->storage_quota_available);
    }

    public function testGetStorageQuotaUsed()
    {
        $user = User::convert(BaseUser::factory()->create());
        $request = StorageRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, $user->storage_quota_used);

        $request->files()->createMany([
            ['path' => 'a.jpg', 'size' => 2],
            ['path' => 'b.jpg', 'size' => 3],
        ]);
        Cache::clear();

        $this->assertSame(5, $user->storage_quota_used);
    }

    public function testGetStorageQuotaRemaining()
    {
        config(['user_storage.user_quota' => 500]);
        $user = User::convert(BaseUser::factory()->create());

        $this->assertSame(500, $user->storage_quota_remaining);
        Cache::clear();

        $user->storage_quota_available = 300;
        $this->assertSame(300, $user->storage_quota_remaining);
        Cache::clear();

        StorageRequestFile::factory()->create([
            'size' => 100,
            'storage_request_id' => StorageRequest::factory()->create([
                'user_id' => $user->id,
            ])->id,
        ]);

        $this->assertSame(200, $user->storage_quota_remaining);
    }
}
