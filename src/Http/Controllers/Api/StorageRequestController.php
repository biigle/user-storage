<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequest;
use Biigle\Modules\UserStorage\Jobs\CleanupStorageRequest;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Http\Request;

class StorageRequestController extends Controller
{
    /**
     * Initialize a new storage request
     *
     * @api {post} storage-requests Create a new storage request
     * @apiGroup UserStorage
     * @apiName StoreStorageRequest
     * @apiPermission editor
     * @apiDescription When a new storage request is created, files can be uploaded for it. Then it must be submitted for review by the admins.
     *
     * @param StoreStorageRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStorageRequest $request)
    {
        return StorageRequest::create([
            'user_id' => $request->user()->id,
        ]);
    }

    /**
     * Delete a storage request with all its files.
     *
     * @api {delete} storage-requests/:id Delete a storage request
     * @apiGroup UserStorage
     * @apiName DestroyStorageRequest
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $sr = StorageRequest::findOrFail($id);
        $this->authorize('destroy', $sr);
        if (!empty($sr->files)) {
            CleanupStorageRequest::dispatch($sr);
        }
        $sr->delete();
    }
}
