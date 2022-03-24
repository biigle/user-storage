<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Views;

use Biigle\Role;
use Biigle\Tests\UserTest;
use TestCase;

class StorageRequestControllerTest extends TestCase
{
    public function testCreate()
    {
        $this->get('storage-requests/create')->assertRedirect('login');
        $user = UserTest::create([
            'role_id' => Role::guestId(),
        ]);

        $this->actingAs($user)
            ->get('storage-requests/create')
            ->assertStatus(403);

        $user->role_id = Role::editorId();
        $user->save();

        $this->actingAs($user)
            ->get('storage-requests/create')
            ->assertViewIs('user-storage::create');
    }

    public function testIndex()
    {
        $this->get('storage-requests')->assertRedirect('login');
        $user = UserTest::create([
            'role_id' => Role::guestId(),
        ]);

        $this->actingAs($user)
            ->get('storage-requests')
            ->assertStatus(403);

        $user->role_id = Role::editorId();
        $user->save();

        $this->actingAs($user)
            ->get('storage-requests')
            ->assertViewIs('user-storage::index');
    }
}
