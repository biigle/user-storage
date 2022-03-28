<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\ApproveStorageRequest;
use Biigle\Modules\UserStorage\Http\Requests\ExtendStorageRequest;
use Biigle\Modules\UserStorage\Http\Requests\RejectStorageRequest;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequest;
use Biigle\Modules\UserStorage\Http\Requests\UpdateStorageRequest;
use Biigle\Modules\UserStorage\Jobs\ApproveStorageRequest as ApproveStorageRequestJob;
use Biigle\Modules\UserStorage\Notifications\StorageRequestRejected;
use Biigle\Modules\UserStorage\Notifications\StorageRequestSubmitted;
use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Http\Request;
use Notification;

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
     * Show a storage request
     *
     * @api {get} storage-requests/:id Show a storage request
     * @apiGroup UserStorage
     * @apiName ShowStorageRequest
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $request = StorageRequest::findOrFail($id);
        $this->authorize('access', $request);

        return $request;
    }

    /**
     * Submit a storage request
     *
     * @api {put} storage-requests/:id Submit a storage request
     * @apiGroup UserStorage
     * @apiName SubmitStorageRequest
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param UpdateStorageRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStorageRequest $request)
    {
        $storageRequest = $request->storageRequest;
        $storageRequest->update(['submitted_at' => now()]);
        Notification::route('mail', config('biigle.admin_email'))
            ->notify(new StorageRequestSubmitted($storageRequest));
    }

    /**
     * Approve a storage request
     *
     * @api {post} storage-requests/:id/approve Approve a storage request
     * @apiGroup UserStorage
     * @apiName ApproveStorageRequest
     * @apiPermission admin
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param ApproveStorageRequest $request
     * @return \Illuminate\Http\Response
     */
    public function approve(ApproveStorageRequest $request)
    {
        $months = config('user_storage.expires_months');
        $request->storageRequest->update(['expires_at' => now()->addMonths($months)]);
        ApproveStorageRequestJob::dispatch($request->storageRequest);
    }

    /**
     * Reject a storage request
     *
     * @api {post} storage-requests/:id/reject Reject a storage request
     * @apiGroup UserStorage
     * @apiName RejectStorageRequest
     * @apiPermission admin
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param RejectStorageRequest $request
     * @return \Illuminate\Http\Response
     */
    public function reject(RejectStorageRequest $request)
    {
        $storageRequest = $request->storageRequest;
        $storageRequest->user->notify(
            new StorageRequestRejected($request->input('reason'))
        );
        $storageRequest->delete();
    }

    /**
     * Extend a storage request
     *
     * @api {post} storage-requests/:id/extend Extend a storage request
     * @apiGroup UserStorage
     * @apiName ExtendStorageRequest
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @param ExtendStorageRequest $request
     * @return \Illuminate\Http\Response
     */
    public function extend(ExtendStorageRequest $request)
    {
        $months = config('user_storage.expires_months');
        $request->storageRequest->update(['expires_at' => now()->addMonths($months)]);
        $request->storageRequest->setHidden(['files']);

        return $request->storageRequest;
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
        $storageRequest = StorageRequest::findOrFail($id);
        $this->authorize('destroy', $storageRequest);
        $storageRequest->delete();
    }
}
