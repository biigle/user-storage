<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\DestroyStorageRequestFile;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequestFile;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\User;
use DB;

class StorageRequestFileController extends Controller
{
    /**
     * Store a file for a new storage request.
     *
     * @api {post} storage-requests/:id/files Upload a file for a storage request
     * @apiGroup UserStorage
     * @apiName StoreStorageRequestFile
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @apiParam (Required arguments) {File} file The file to add to the storage request.
     *
     * @apiParam (Optional arguments) {string} prefix Optional prefix to prepend to the filename. Use slashes to create directories.
     *
     * @param StoreStorageRequestFile $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStorageRequestFile $request)
    {
        DB::transaction(function () use ($request) {
            $sr = $request->storageRequest;

            $file = $request->file('file');
            $filePath = $request->getFilePath();
            $disk = config('user_storage.pending_disk');

            $user = User::convert($sr->user);
            $user->storage_quota_used += $file->getSize();
            $user->save();

            $sr->files = array_merge($sr->files, [$filePath]);
            $sr->save();

            $file->storeAs($sr->getPendingPath(), $filePath, $disk);
        });
    }

    /**
     * Delete files of a storage request
     *
     * @api {delete} storage-requests/:id/files Delete files
     * @apiGroup UserStorage
     * @apiName DestroyStorageRequestFile
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @apiParam (Required arguments) {File[]} files Array of file paths that should be deleted from this storage request.
     *
     * @param DestroyStorageRequestFile $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyStorageRequestFile $request)
    {
        DeleteStorageRequestFiles::dispatch($request->storageRequest, $request->input('files'));
    }
}
