<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\StorageRequest;
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
        $this->model->user->delete();
        $this->assertNull($this->model->fresh());
    }

    public function testGetSetFiles()
    {
        $files = ['a.jpg', 'b.jpg'];
        $this->model->files = $files;
        $this->model->save();
        $this->assertSame($files, $this->model->fresh()->files);
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
}
