<?php

namespace Biigle\Modules\UserStorage\Observers;

use Biigle\Modules\UserStorage\StorageRequest;

class UserObserver
{
    /**
     * Remove storage request files of a user that should be deleted.
     *
     * @param \Biigle\User $user
     */
    public function deleting($user)
    {
        StorageRequest::where('user_id', $user->id)
            ->eachById(function ($request) {
                // Do this manually because it dispatches the delete files job.
                $request->delete();
            });
    }
}
