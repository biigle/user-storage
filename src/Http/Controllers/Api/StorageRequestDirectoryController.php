<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\DestroyStorageRequestDirectory;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;

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
        DeleteStorageRequestFiles::dispatch($request->storageRequest, $request->files);

        $request->storageRequest->files = array_values(array_diff(
            $request->storageRequest->files, $request->files
        ));
        $request->storageRequest->save();
    }
}