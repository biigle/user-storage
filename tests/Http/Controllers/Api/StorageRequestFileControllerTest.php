<?php

namespace Biigle\Tests\Modules\UserStorage\Http\Controllers\Api;

use Cache;
use Mockery;
use Storage;
use Exception;
use ApiTestCase;
use RuntimeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Biigle\Modules\UserStorage\User;
use League\Flysystem\UnableToWriteFile;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;

class StorageRequestFileControllerTest extends ApiTestCase
{
    public function testStore()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->doTestApiRoute('POST', "/api/v1/storage-requests/{$id}/files");

        $this->beUser();
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(403);

        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files")->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => 'abc'])
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);

        $this->assertTrue($disk->exists("request-{$id}/test.jpg"));
        $file = $request->files()->first();
        $this->assertNotNull($file);
        $this->assertSame('test.jpg', $file->path);
        $this->assertSame(44074, $file->size);
        $this->assertNull($file->total_chunks);
        $this->assertNull($file->received_chunks);
    }

    public function testStoreChunks()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
            ])
            // Chunk total must be given with index.
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_total' => 2,
            ])
            // Chunk index must be given with total.
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => -1,
                'chunk_total' => 2,
            ])
            // Chunk index must not be negative.
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 1,
            ])
            // Chunk total must be larger than 1.
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 2,
                'chunk_total' => 2,
            ])
            // Chunk index must be lower than chunk total.
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 2,
                'chunk_total' => 2,
                'retry' => 'test'
            ])
            // retry must be boolean
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
                'retry' => true,
            ])
            ->assertStatus(200);

        $this->assertTrue($disk->exists("request-{$id}/test.jpg.0"));
        $f = $request->files()->first();
        $this->assertNotNull($f);
        $this->assertSame('test.jpg', $f->path);
        $this->assertSame(44074, $f->size);
        $this->assertSame(2, $f->total_chunks);
        $this->assertSame([0], $f->received_chunks);
        $this->assertEquals(3, $f->retry_count);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 2,
                'retry' => true,
            ])
            ->assertStatus(200);

        $this->assertTrue($disk->exists("request-{$id}/test.jpg.1"));
        $f->refresh();
        $this->assertSame(88148, $f->size);
        $this->assertSame(2, $f->total_chunks);
        $this->assertSame([0, 1], $f->received_chunks);
        $this->assertEquals(5, $f->retry_count);
    }

    public function testStoreDenyTooLargeNotChunked()
    {
        config(['user_storage.upload_chunk_size' => 40000]);
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
            ])
            ->assertStatus(422);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(422);
    }

    public function testStoreTwo()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);

        $request->refresh();
        $files = $request->files()->orderBy('id')->pluck('path')->toArray();
        $this->assertSame(['test.jpg', 'test2.jpg'], $files);
    }

    public function testStorePrefix()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc/def',
                'file' => $file,
            ])
            ->assertStatus(201);

        $this->assertTrue($disk->exists("request-{$id}/abc/def/test.jpg"));
        $this->assertSame('abc/def/test.jpg', $request->files()->first()->path);
    }

    public function testStoreFilenameAndPrefixLength()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/',
                'file' => $file,
            ])
            ->assertStatus(422);
    }

    public function testStorePrefixTrailingSlash()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc/def/',
                'file' => $file,
            ])
            ->assertStatus(201);

        $this->assertTrue($disk->exists("request-{$id}/abc/def/test.jpg"));
        $this->assertSame('abc/def/test.jpg', $request->files()->first()->path);
    }

    public function testStorePrefixDoubleSlash()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc//def',
                'file' => $file,
            ])
            ->assertStatus(201);

        $this->assertTrue($disk->exists("request-{$id}/abc/def/test.jpg"));
        $this->assertSame('abc/def/test.jpg', $request->files()->first()->path);
    }

    public function testStorePrefixUnicode()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc/d北f1',
                'file' => $file,
            ])
            ->assertStatus(201);

        $this->assertTrue($disk->exists("request-{$id}/abc/d北f1/test.jpg"));
        $this->assertSame('abc/d北f1/test.jpg', $request->files()->first()->path);
    }

    public function testStorePrefixInvalidCharactersStart()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => '"abc/def',
                'file' => $file,
            ])
            ->assertStatus(422);
    }

    public function testStorePrefixInvalidCharactersEnd()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc/def"',
                'file' => $file,
            ])
            ->assertStatus(422);
    }

    public function testStorePrefixInvalidInbetween()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'prefix' => 'abc\def',
                'file' => $file,
            ])
            ->assertStatus(422);
    }

    public function testStoreTooLargeQuota()
    {
        config(['user_storage.user_quota' => 10000]);

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreChunkTooLargeQuota()
    {
        Bus::fake();
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        Cache::clear();
        $f = $request->files()->first();

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 2,
            ])
            ->assertStatus(422);

        Bus::assertDispatched(function (DeleteStorageRequestFile $job) {
            $this->assertSame('test.jpg', $job->path);
            $this->assertSame([0], $job->chunks);

            return true;
        });
    }

    public function testStoreTooLargeFile()
    {
        config(['user_storage.max_file_size' => 10000]);

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreChunkTooLargeFile()
    {
        Bus::fake();
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.max_file_size' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        Cache::clear();
        $f = $request->files()->first();

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 2,
            ])
            ->assertStatus(422);


        Bus::assertDispatched(function (DeleteStorageRequestFile $job) {
            $this->assertSame('test.jpg', $job->path);
            $this->assertSame([0], $job->chunks);

            return true;
        });
    }

    public function testStoreMimeType()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.txt", 'test.txt', 'text/plain', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);

        // Attempt to spoof MIME type.
        $file = new UploadedFile(__DIR__."/../../../files/test.txt", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreChunkMimeType()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        $file = new UploadedFile(__DIR__."/../../../files/test.txt", 'test.jpg', 'text/plain', null, true);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);
    }

    public function testStoreChunkChunkTotalMismatch()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 3,
            ])
            ->assertStatus(422);
    }

    public function testStoreChunkChunkIndexExists()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $res1 = $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        $res2 = $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 0,
                'chunk_total' => 2,
            ])
            ->assertStatus(200);

        // Filemodel is just returned
        $this->assertEquals($res1->getContent(), $res2->getContent());
    }

    public function testStoreChunkFirstChunkFirst()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);

        $this->be($request->user);

        $this->postJson("/api/v1/storage-requests/{$id}/files", [
                'file' => $file,
                'chunk_index' => 1,
                'chunk_total' => 2,
            ])
            ->assertStatus(422);
    }

    public function testStoreRequestSubmitted()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create([
            'submitted_at' => '2022-03-10 10:55:00',
        ]);
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreExistsInSameRequest()
    {
        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => $request->id,
            'size' => 123,
        ]);
        $id = $request->id;

        $disk->put("request-{$id}/test.jpg", 'abc');

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(200);

        $this->assertNotSame('abc', $disk->get("request-{$id}/test.jpg"));
        $this->assertSame(1, $request->files()->count());
        $this->assertSame(44074, $request->files()->first()->size);
    }

    public function testStoreExistsInOtherRequest()
    {
        $request = StorageRequest::factory()->create();

        $file = StorageRequestFile::factory()->create([
            'path' => 'test.jpg',
            'storage_request_id' => StorageRequest::factory()->create([
                'user_id' => $request->user_id,
            ])->id,
        ]);

        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->be($request->user);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);

        $fileDuplicate = StorageRequestFile::factory()->create([
            'path' => 'abc/test.jpg',
            'storage_request_id' => StorageRequest::factory()->create([
                'user_id' => $request->user_id,
            ])->id,
        ]);

        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $fileDuplicate])
            ->assertStatus(422);
    
    }

    public function testStoreExceedsQuota()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $disk->put("user-{$request->user->id}/test.jpg", 'abc');

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);
        Cache::clear();
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(422);
    }

    public function testStoreExceedsConfigQuotaButNotUserQuota()
    {
        config(['user_storage.pending_disk' => 'test']);
        config(['user_storage.user_quota' => 50000]);
        $disk = Storage::fake('test');

        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $user = User::convert($request->user);
        $user->storage_quota_available = 100000;
        $user->save();
        $request->user->refresh();

        $disk->put("user-{$request->user->id}/test.jpg", 'abc');

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test2.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(201);
    }

    public function testStoreMaintenanceMode()
    {
        config(['user_storage.maintenance_mode' => true]);
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $this->be($request->user);
        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $this->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file])
            ->assertStatus(403);
    }

    public function testStoreRetryOnFailure()
    {
        $request = StorageRequest::factory()->create();
        $id = $request->id;

        $file = new UploadedFile(__DIR__."/../../../files/test.jpg", 'test.jpg', 'image/jpeg', null, true);
        $file = Mockery::mock($file);
        $file->shouldReceive('storeAs')
            ->times(2)
            ->andThrow(UnableToWriteFile::class);

        $this->be($request->user);
        $this->expectException(Exception::class);
        $this->withoutExceptionHandling()
            ->postJson("/api/v1/storage-requests/{$id}/files", ['file' => $file]);
    }

    public function testShow() {
        $request = StorageRequest::factory()->create();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'b.jpg',
            'storage_request_id' => $request->id,
        ]);
        $id = $request->id;

        config(['user_storage.pending_disk' => 'test']);
        $disk = Storage::fake('test');
        $disk->buildTemporaryUrlsUsing(function () {
            // Act as if the storage disk driver does not support temporary URLs.
            throw new RuntimeException;
        });
        $disk->put("request-{$id}/a.jpg", 'abc');

        $this->doTestApiRoute('GET', "/api/v1/storage-request-files/{$file->id}");

        $this->beUser();
        $this->get("/api/v1/storage-request-files/{$file->id}")->assertStatus(404);

        $this->be($request->user);
        $this->get("/api/v1/storage-request-files/{$file->id}")->assertStatus(404);

        $this->beGlobalReviewer();
        $this->get("/api/v1/storage-request-files/{$file->id}")->assertStatus(200);
        $this->get("/api/v1/storage-request-files/{$file2->id}")->assertStatus(404);
    }

    public function testShowApproved() {
        $request = StorageRequest::factory()->create([
            'expires_at' => '2022-03-28 14:03:00',
        ]);
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
            'storage_request_id' => $request->id,
        ]);

        config(['user_storage.storage_disk' => 'test']);
        $disk = Storage::fake('test');
        $disk->buildTemporaryUrlsUsing(function () {
            // Act as if the storage disk driver does not support temporary URLs.
            throw new RuntimeException;
        });
        $disk->put("user-{$request->user_id}/a.jpg", 'abc');

        $this->beGlobalReviewer();
        $this->get("/api/v1/storage-request-files/{$file->id}")->assertStatus(200);
    }

    public function testShowPublic() {
        $mock = Mockery::mock();
        $mock->shouldReceive('temporaryUrl')->once()->andReturn('myurl');
        Storage::shouldReceive('disk')->andReturn($mock);

        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
        ]);

        $this->beGlobalReviewer();
        $this->get("/api/v1/storage-request-files/{$file->id}")
            ->assertRedirect('myurl');
    }

    public function testDestory()
    {
        Bus::fake();
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
        ]);
        $file2 = StorageRequestFile::factory()->create([
            'path' => 'b.jpg',
            'storage_request_id' => $file->storage_request_id,
        ]);

        $this->doTestApiRoute('DELETE', "/api/v1/storage-request-files/{$file->id}");

        $this->beUser();
        $this->deleteJson("/api/v1/storage-request-files/{$file->id}")
            ->assertStatus(403);

        $this->be($file->request->user);
        $this->deleteJson("/api/v1/storage-request-files/{$file->id}")
            ->assertStatus(200);

        Bus::assertDispatched(function (DeleteStorageRequestFile $job) {
            $this->assertSame('a.jpg', $job->path);

            return true;
        });
        
    }

    public function testDestoryLastFile()
    {
        $file = StorageRequestFile::factory()->create([
            'path' => 'a.jpg',
        ]);

        $this->be($file->request->user);
        $this->deleteJson("/api/v1/storage-request-files/{$file->id}")
            ->assertStatus(422);
    }

}
