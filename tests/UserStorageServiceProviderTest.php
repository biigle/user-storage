<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\UserStorageServiceProvider;
use TestCase;

class UserStorageServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $this->assertTrue(class_exists(UserStorageServiceProvider::class));
    }
}
