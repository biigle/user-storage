<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Views;

use Biigle\Image;
use Biigle\Video;
use Biigle\Http\Controllers\Views\Controller;
use Biigle\Modules\UserStorage\StorageRequest;

class StorageRequestController extends Controller
{
    /**
     * Show the view to create a new storage request.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', StorageRequest::class);

        return view('user-storage::create', [
            'allowedMimeTypes' => implode(',', array_merge(Image::MIMES, Video::MIMES)),
        ]);
    }
}
