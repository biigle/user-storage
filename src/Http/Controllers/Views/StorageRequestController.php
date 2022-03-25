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
     * Show the view to index storage requests of the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('create', StorageRequest::class);

        $user = User::convert($request->user());
        $usedQuota = $user->storage_quota_used;
        $availableQuota = $user->storage_quota_available;

        $requests = StorageRequest::where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->each(function ($request) {
                $request->setHidden(['files']);
            });

        $expireDate = now()->addWeeks(config('user_storage.about_to_expire_weeks'));

        return view('user-storage::index', [
            'usedQuota' => $usedQuota,
            'availableQuota' => $availableQuota,
            'requests' => $requests,
            'expireDate' => $expireDate,
        ]);
    }

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
