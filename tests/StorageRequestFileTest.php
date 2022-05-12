<?php

namespace Biigle\Tests\Modules\UserStorage;

use Biigle\Modules\UserStorage\StorageRequestFile;
use ModelTestCase;

class StorageRequestFileTest extends ModelTestCase
{
    /**
     * The model class this class will test.
     */
    protected static $modelClass = StorageRequestFile::class;

    public function testAttributes()
    {
        $this->assertNotNull($this->model->path);
        $this->assertNotNull($this->model->request);
        $this->assertNotNull($this->model->size);
    }

    public function testRequestDeletedCascade()
    {
        $this->model->request->delete();
        $this->assertFalse($this->model->exists());
    }
}
