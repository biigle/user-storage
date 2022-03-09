<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequest;

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
     * Delete a MAIA job.
     *
     * @api {delete} maia-jobs/:id Delete a MAIA job
     * @apiGroup UserStorage
     * @apiName DestroyStorageRequest
     * @apiPermission projectEditor
     *
     * @apiParam {Number} id The job ID.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }
}
