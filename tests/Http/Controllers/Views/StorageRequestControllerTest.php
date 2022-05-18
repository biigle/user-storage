<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Views;

use Biigle\Modules\UserStorage\StorageRequest;
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

        $this->markTestIncomplete('Implement chunked upload.');
    }

    public function testCreateMaintenanceMode()
    {
        config(['user_storage.maintenance_mode' => true]);
        $user = UserTest::create([
            'role_id' => Role::editorId(),
        ]);
        $this->actingAs($user)
            ->get('storage-requests/create')
            ->assertStatus(403);
    }

    public function testIndex()
    {
        $this->get('storage-requests')->assertRedirect('login');
        $user = UserTest::create([
            'role_id' => Role::guestId(),
        ]);

        $this->actingAs($user)
            ->get('storage-requests')
            ->assertViewIs('user-storage::index');
    }

    public function testReview()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->get("storage-requests/{$id}/review")->assertRedirect('login');

        $this->actingAs($request->user)
            ->get("storage-requests/{$id}/review")
            ->assertStatus(403);

        $user = UserTest::create([
            'role_id' => Role::editorId(),
        ]);

        $this->actingAs($user)
            ->get("storage-requests/{$id}/review")
            ->assertStatus(403);

        $user->role_id = Role::adminId();
        $user->save();

        $this->actingAs($user)
            ->get("storage-requests/{$id}/review")
            ->assertViewIs('user-storage::review');

        $request->update(['expires_at' => '2022-03-28 10:40:00']);
        $this->actingAs($user)
            ->get("storage-requests/{$id}/review")
            ->assertStatus(404);
    }
}
