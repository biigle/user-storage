<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\Http\Requests\DestroyStorageRequestFile;
use Biigle\Modules\UserStorage\Http\Requests\StoreStorageRequestFile;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Biigle\Modules\UserStorage\User;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;
use Storage;

class StorageRequestFileController extends Controller
{
    /**
     * Times to retry uploading a new file to storage.
     *
     * @var int
     */
    const RETRY_UPLOAD = 2;

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

            $fileModel = $sr->files()->where('path', $filePath)->first();
            if ($fileModel) {
                $fileModel->update(['size' => $file->getSize()]);
            } else {
                $sr->files()->create([
                    'path' => $filePath,
                    'size' => $file->getSize(),
                ]);
            }

            // Retry the upload a few times, as we observed storage backends that threw
            // random errors which did not happen again after a retry.
            for ($i = 0; $i < self::RETRY_UPLOAD; $i++) {
                try {
                    $success = $file->storeAs($sr->getPendingPath(), $filePath, [
                        'disk' => $disk,
                        'contentType' => $file->getMimeType(),
                    ]);
                } catch (UnableToWriteFile $e) {
                    $success = false;
                }

                if ($success === true) {
                    break;
                }
            }

            if ($success === false) {
                throw new Exception("Unable to save file.");
            }
        });
    }

    /**
     * Show a file of a storage request.
     *
     * @api {get} storage-request-files/:id Show a file
     * @apiGroup UserStorage
     * @apiName ShowStorageRequestFile
     * @apiPermission admin
     *
     * @apiParam {Number} id The storage request file ID
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

        $file = StorageRequestFile::with('request')->findOrFail($id);

        if (is_null($file->request->expires_at)) {
                $disk = Storage::disk(config('user_storage.pending_disk'));
                $path = $file->request->getPendingPath($file->path);
            } else {
                $disk = Storage::disk(config('user_storage.storage_disk'));
                $path = $file->request->getStoragePath($file->path);
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
     * @api {delete} storage-request-files/:id Delete files
     * @apiGroup UserStorage
     * @apiName DestroyStorageRequestFile
     * @apiPermission storageRequestOwner
     *
     * @apiParam {Number} id The storage request file ID.
     *
     * @param DestroyStorageRequestFile $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyStorageRequestFile $request)
    {
        DeleteStorageRequestFile::dispatch($request->file);
        $request->file->delete();
    }
}
