<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\UserStorage\Jobs\ApproveStorageRequest;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestDirectory;
use Biigle\Modules\UserStorage\Jobs\AssembleChunkedFile;
use Biigle\Modules\UserStorage\Jobs\RejectStorageRequest;
use Biigle\Modules\UserStorage\Notifications\StorageRequestRejected;
use Biigle\Modules\UserStorage\Notifications\StorageRequestSubmitted;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\User;
use Illuminate\Bus\PendingBatch;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Storage;

class StorageRequestControllerTest extends ApiTestCase
{
    public function testShow()
    {
        $request = StorageRequest::factory()->create();
        StorageRequestFile::factory()->create([
            'storage_request_id' => $request->id,
        ]);
        $request->load('files');
        $id = $request->id;

        $this->doTestApiRoute('GET', "/api/v1/storage-requests/{$id}");

        $this->beUser();
        $this->getJson("/api/v1/storage-requests/{$id}")->assertStatus(403);

        $this->be($request->user);
        unset($request->user);
        $this->getJson("/api/v1/storage-requests/{$id}")
            ->assertStatus(200)
            ->assertExactJson($request->toArray());
    }

    public function testStore()
    {
        $this->doTestApiRoute('POST', "/api/v1/storage-requests");

        $this->beGlobalGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(403);

        $this->beGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);

        $request = StorageRequest::first();
        $this->assertNotNull($request);
        $this->assertSame($this->guest()->id, $request->user_id);
        $this->assertNull($request->expires_at);
        $this->assertFalse($request->files()->exists());
    }

    public function testStoreLimitOpenRequests()
    {
        config(['user_storage.max_pending_requests' => 2]);
        $this->beGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
        $this->postJson("/api/v1/storage-requests")->assertStatus(422);

        $request = StorageRequest::first();
        $request->update(['expires_at' => now()->addYear()]);

        $this->postJson("/api/v1/storage-requests")->assertStatus(201);
    }

    public function testStoreMaintenanceMode()
    {
        config(['user_storage.maintenance_mode' => true]);
        $this->beGuest();
        $this->postJson("/api/v1/storage-requests")->assertStatus(403);
    }

    public function testUpdate()
    {
        Bus::fake();
        Notification::fake();
        $request = StorageRequest::factory()->create();
        StorageRequestFile::factory()->create([
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('PUT', "/api/v1/storage-requests/{$id}");

        $this->beGuest();
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(403);

        $this->be($request->user);
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(200);
        $this->assertNotNull($request->fresh()->submitted_at);

        Notification::assertSentTo(new AnonymousNotifiable, StorageRequestSubmitted::class);
        Bus::assertNothingDispatched();
    }

    public function testUpdateEmpty()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(422);
    }

    public function testUpdateAlreadyUpdated()
    {
        $request = StorageRequest::factory()->create([
            'submitted_at' => '2022-03-11 16:03:00',
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(404);
    }

    public function testUpdateMaintenanceMode()
    {
        config(['user_storage.maintenance_mode' => true]);
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(403);
    }

    public function testUpdateWithChunkedFiles()
    {
        Bus::fake();
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = StorageRequestFile::factory()->create([
            'storage_request_id' => $request->id,
            'received_chunks' => [0, 2, 1],
            'total_chunks' => 3,
        ]);

        $this->be($request->user);
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertBatched(function (PendingBatch $batch) use ($file) {
            $this->assertCount(1, $batch->jobs);
            $this->assertInstanceOf(AssembleChunkedFile::class, $batch->jobs[0]);
            $this->assertSame($file->id, $batch->jobs[0]->file->id);

            return true;
        });
    }

    public function testUpdateWithUnfinishedChunkedFiles()
    {
        Bus::fake();
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = StorageRequestFile::factory()->create([
            'storage_request_id' => $request->id,
            'received_chunks' => [0],
            'total_chunks' => 2,
        ]);

        $this->be($request->user);
        // Chunked file did not receive all chunks yet.
        $this->putJson("/api/v1/storage-requests/{$id}")->assertStatus(422);
    }

    public function testApprove()
    {
        Bus::fake();

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/approve");

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(403);

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(200);

        $this->assertNotNull($request->fresh()->expires_at);
        Bus::assertDispatched(function (ApproveStorageRequest $job) use ($request) {
            return $job->request->id === $request->id;
        });
    }

    public function testApproveEmpty()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(422);
    }

    public function testApproveAlreadyApproved()
    {
        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-11 11:22:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/approve")->assertStatus(404);
    }

    public function testReject()
    {
        Bus::fake();
        Notification::fake();

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/reject");

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/reject")->assertStatus(403);

        $this->beGlobalAdmin();
        // Needs a reason
        $this->postJson("/api/v1/storage-requests/{$id}/reject")->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/reject", [
                'reason' => 'because',
            ])
            ->assertStatus(200);

        Bus::assertDispatched(function (DeleteStorageRequestDirectory $job) use ($request) {
            return $job->path === $request->getPendingPath();
        });
        $this->assertNull($request->fresh());
        Notification::assertSentTo([$request->user], StorageRequestRejected::class);
    }

    public function testRejectAlreadyApproved()
    {
        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-11 11:22:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->beGlobalAdmin();
        $this->postJson("/api/v1/storage-requests/{$id}/reject")->assertStatus(404);
    }

    public function testExtend()
    {
        $expires = now()->addWeeks(3);
        $request = StorageRequest::factory()->create([
            'expires_at' => $expires,
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/extend");

        $this->beGuest();
        $this->postJson("/api/v1/storage-requests/{$id}/extend")->assertStatus(403);

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/extend")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $request->id]);

        $request->refresh();
        $this->assertTrue($request->expires_at > $expires);
    }

    public function testExtendNotAboutToExpire()
    {
        $request = StorageRequest::factory()->create([
            'expires_at' => now()->addWeeks(5),
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/extend")->assertStatus(422);
    }

    public function testExtendNotApproved()
    {
        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/extend")->assertStatus(422);
    }

    public function testDestroy()
    {
        Bus::fake();

        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-10 15:28:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->doTestApiRoute('DELETE', "/api/v1/storage-requests/{$id}");

        $this->beUser();
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(403);

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertBatched(function (PendingBatch $batch) use ($file) {
            $this->assertEquals(1, $batch->jobs->count());
            $this->assertEquals($file->path, $batch->jobs->first()->path);
            return true;
        });
        $this->assertNull($request->fresh());
    }

    public function testDestroyPending()
    {
        Bus::fake();

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        $this->be($request->user);
        $this->deleteJson("/api/v1/storage-requests/{$id}")->assertStatus(200);

        Bus::assertDispatched(DeleteStorageRequestDirectory::class);
        $this->assertNull($request->fresh());
    }
}
