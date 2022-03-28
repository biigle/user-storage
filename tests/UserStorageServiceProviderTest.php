<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\Support\FilesystemManager;
use Storage;
use TestCase;

class UserStorageServiceProviderTest extends TestCase
{
    public function testOverrideStorageFacade()
    {
        $this->assertInstanceOf(FilesystemManager::class, Storage::getFacadeRoot());
    }
}
