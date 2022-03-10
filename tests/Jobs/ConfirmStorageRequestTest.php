<?php

namespace Biigle\Tests\Modules\UserStorage\Jobs;

use Biigle\Modules\UserStorage\Jobs\ConfirmStorageRequest;
use Biigle\Modules\UserStorage\StorageRequest;
use Storage;
use TestCase;

class ConfirmStorageRequestTest extends TestCase
{
    public function testHandle()
    {
        // Move the files
        // Delete pending files
        // Set expires_at (based on config value)
        // Notify request creator
        $this->markTestIncomplete();

        // Implement scheduled job to check for expired requests.
    }
}
