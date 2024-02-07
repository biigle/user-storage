<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\User;
use Biigle\Modules\UserStorage\User as StorageUser;

class StorageRequestUserControllerTest extends ApiTestCase
{
    public function testStore()
    {
        config(['user_storage.user_quota' => 100]);
        $user = StorageUser::convert($this->user());

        $this->doTestApiRoute('POST', "/api/v1/users/{$user->id}/storage-request-quota");

        $this->beAdmin();
        $this->postJson("/api/v1/users/{$user->id}/storage-request-quota")
            ->assertStatus(403);

        $this->be($user);
        $this->postJson("/api/v1/users/{$user->id}/storage-request-quota")
            ->assertStatus(403);

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/users/{$user->id}/storage-request-quota")
            ->assertStatus(422);

        $this->assertEquals(100, $user->storage_quota_available);
        $this
            ->postJson("/api/v1/users/{$user->id}/storage-request-quota", [
                'quota' => 200,
            ])
            ->assertStatus(200);

        $this->assertEquals(200, $user->fresh()->storage_quota_available);

        $this
        ->postJson("/api/v1/users/{$user->id}/storage-request-quota", [
            'quota' => "1E+11",
        ])
        ->assertStatus(200);

        $this->assertEquals(1E+11, $user->fresh()->storage_quota_available);
    }
}
