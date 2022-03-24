<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\User;
use Biigle\User as BaseUser;
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

    public function testGetSetStorageQuotaUsed()
    {
        $user = User::convert(BaseUser::factory()->make());

        $this->assertSame(0, $user->storage_quota_used);

        $user->storage_quota_used += 100;
        $this->assertSame(100, $user->storage_quota_used);

        $user->storage_quota_used -= 101;
        $this->assertSame(0, $user->storage_quota_used);
    }

    public function testGetStorageQuotaRemaining()
    {
        config(['user_storage.user_quota' => 500]);
        $user = User::convert(BaseUser::factory()->make());

        $this->assertSame(500, $user->storage_quota_remaining);

        $user->storage_quota_available = 300;
        $this->assertSame(300, $user->storage_quota_remaining);

        $user->storage_quota_used = 100;
        $this->assertSame(200, $user->storage_quota_remaining);
    }
}
