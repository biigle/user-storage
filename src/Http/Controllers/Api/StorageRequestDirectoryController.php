<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\DestroyStorageRequestDirectory;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Queue;

class StorageRequestDirectoryController extends Controller
{
    /**
     * Delete directories of a storage request
     *
     * @api {delete} storage-requests/:id/directories Delete directories
     * @apiGroup UserStorage
     * @apiName DestroyStorageRequestDirectories
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request ID.
     *
     * @apiParam (Required arguments) {File[]} directories Array of directory paths that should be deleted from this storage request.
     *
     * @param DestroyStorageRequestDirectory $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyStorageRequestDirectory $request)
    {
        $request->files->load('request');
        Queue::bulk($request->files->map(function ($file) {
            return new DeleteStorageRequestFile($file);
        })->toArray());

        StorageRequestFile::whereIn('id', $request->files->pluck('id'))->delete();
    }
}
