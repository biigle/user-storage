<?php

namespace Biigle\Tests\Modules\UserStorage\Policies;

use ApiTestCase;
use Biigle\Modules\UserStorage\StorageRequest;

class StorageRequestPolicyTest extends ApiTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->request = StorageRequest::factory()->create(['user_id' => $this->editor()->id]);
    }

    public function testCreate()
    {
        $this->assertFalse($this->globalGuest()->can('create', StorageRequest::class));
        $this->assertTrue($this->user()->can('create', StorageRequest::class));
        $this->assertTrue($this->guest()->can('create', StorageRequest::class));
        $this->assertTrue($this->editor()->can('create', StorageRequest::class));
        $this->assertTrue($this->expert()->can('create', StorageRequest::class));
        $this->assertTrue($this->admin()->can('create', StorageRequest::class));
        $this->assertTrue($this->globalAdmin()->can('create', StorageRequest::class));
    }

    public function testAccess()
    {
        $this->assertFalse($this->globalGuest()->can('access', $this->request));
        $this->assertFalse($this->user()->can('access', $this->request));
        $this->assertFalse($this->guest()->can('access', $this->request));
        $this->assertTrue($this->editor()->can('access', $this->request));
        $this->assertFalse($this->expert()->can('access', $this->request));
        $this->assertFalse($this->admin()->can('access', $this->request));
        $this->assertTrue($this->globalAdmin()->can('access', $this->request));
    }

    public function testUpdate()
    {
        $this->assertFalse($this->globalGuest()->can('update', $this->request));
        $this->assertFalse($this->user()->can('update', $this->request));
        $this->assertFalse($this->guest()->can('update', $this->request));
        $this->assertTrue($this->editor()->can('update', $this->request));
        $this->assertFalse($this->expert()->can('update', $this->request));
        $this->assertFalse($this->admin()->can('update', $this->request));
        $this->assertFalse($this->globalAdmin()->can('update', $this->request));
    }

    public function testConfirm()
    {
        $this->assertFalse($this->globalGuest()->can('confirm', $this->request));
        $this->assertFalse($this->user()->can('confirm', $this->request));
        $this->assertFalse($this->guest()->can('confirm', $this->request));
        $this->assertFalse($this->editor()->can('confirm', $this->request));
        $this->assertFalse($this->expert()->can('confirm', $this->request));
        $this->assertFalse($this->admin()->can('confirm', $this->request));
        $this->assertTrue($this->globalAdmin()->can('confirm', $this->request));
    }

    public function testDestroy()
    {
        $this->assertFalse($this->globalGuest()->can('destroy', $this->request));
        $this->assertFalse($this->user()->can('destroy', $this->request));
        $this->assertFalse($this->guest()->can('destroy', $this->request));
        $this->assertTrue($this->editor()->can('destroy', $this->request));
        $this->assertFalse($this->expert()->can('destroy', $this->request));
        $this->assertFalse($this->admin()->can('destroy', $this->request));
        $this->assertTrue($this->globalAdmin()->can('destroy', $this->request));
    }
}
