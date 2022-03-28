<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\DestroyStorageRequestFile;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequestFile;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use RuntimeException;
use Storage;

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
     * Show a file of a storage request.
     *
     * @api {get} storage-requests/:id/files/:path Show a file
     * @apiGroup UserStorage
     * @apiName ShowStorageRequestFile
     * @apiPermission admin
     *
     * @apiParam {Number} id The storage request ID
     *
     * @apiParam (Required parameters) {String} path The file path
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()->can('sudo')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $storageRequest = StorageRequest::findOrFail($id);
        $path = $request->input('path');

        if (!in_array($path, $storageRequest->files)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (is_null($storageRequest->expires_at)) {
                $disk = Storage::disk(config('user_storage.pending_disk'));
                $path = $storageRequest->getPendingPath($path);
            } else {
                $disk = Storage::disk(config('user_storage.storage_disk'));
                $path = $storageRequest->getStoragePath($path);
            }

        try {
            $url = $disk->temporaryUrl($path, now()->addDay());

            return redirect($url);
        } catch (RuntimeException $e) {
            // Temporary URLs not supported.
            // Continue with code below.
        }

        try {
            $filename = basename($path);

            return $disk->download($path, $filename, [
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (UnableToRetrieveMetadata | UnableToReadFile $e) {
            abort(Response::HTTP_NOT_FOUND);
        }
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
        $files = $request->input('files');
        DeleteStorageRequestFiles::dispatch($request->storageRequest, $files);

        $request->storageRequest->files = array_values(array_diff(
            $request->storageRequest->files, $files
        ));
        $request->storageRequest->save();
    }
}
