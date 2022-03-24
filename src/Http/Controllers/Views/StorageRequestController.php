<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Views;

use Biigle\Image;
use Biigle\Video;
use Biigle\Http\Controllers\Views\Controller;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\Request;

class StorageRequestController extends Controller
{
    /**
     * Show the view to create a new storage request.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', StorageRequest::class);

        $maxSize = User::convert($request->user())->storage_quota_remaining;

        return view('user-storage::create', [
            'allowedMimeTypes' => implode(',', array_merge(Image::MIMES, Video::MIMES)),
            'maxSize' => $maxSize,
        ]);
    }
}
