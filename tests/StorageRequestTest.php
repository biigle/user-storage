<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Support\Facades\Bus;
use ModelTestCase;

class StorageRequestTest extends ModelTestCase
{
    /**
     * The model class this class will test.
     */
    protected static $modelClass = StorageRequest::class;

    public function testAttributes()
    {
        $this->assertNotNull($this->model->user);
        $this->assertNotNull($this->model->created_at);
        $this->assertNotNull($this->model->updated_at);
        $this->assertNull($this->model->expires_at);
        $this->assertNull($this->model->submitted_at);
    }

    public function testUserDeletedCascade()
    {
        Bus::fake();
        // The delete files job is only dispatched if the request has files.
        $this->model->files()->save(StorageRequestFile::factory()->make());
        $this->model->user->delete();
        $this->assertNull($this->model->fresh());
        Bus::assertDispatched(DeleteStorageRequestFiles::class);
    }

    public function testGetPendingPath()
    {
        $id = $this->model->id;
        $this->assertSame("request-{$id}", $this->model->getPendingPath());
        $this->assertSame("request-{$id}/abc", $this->model->getPendingPath('abc'));
    }

    public function testGetStoragePath()
    {
        $id = $this->model->user_id;
        $this->assertSame("user-{$id}", $this->model->getStoragePath());
        $this->assertSame("user-{$id}/abc", $this->model->getStoragePath('abc'));
    }

    public function testGetCreatedAtForHumans()
    {
        $this->assertNotNull($this->model->created_at_for_humans);
    }

    public function testGetExpiresAtForHumans()
    {
        $this->assertNull($this->model->expires_at_for_humans);
        $this->model->expires_at = now();
        $this->assertNotNull($this->model->expires_at_for_humans);
    }

    public function testGetFilesCount()
    {
        $this->assertSame(0, $this->model->files_count);
        $this->model->files()->save(StorageRequestFile::factory()->make());
        $this->assertSame(1, $this->model->files_count);
    }
}
