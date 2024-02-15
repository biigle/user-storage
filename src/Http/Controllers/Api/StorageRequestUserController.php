<?php

namespace Biigle\Modules\UserStorage\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserStorage\User;
use Illuminate\Http\Request;

class StorageRequestUserController extends Controller
{
    /**
     * Update the storage quota of a user
     *
     * @api {post} users/:id/storage-request-quota Update storage quota
     * @apiGroup UserStorage
     * @apiName StoreStorageQuota
     * @apiPermission globalAdmin
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'quota' => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($id);
        $user->storage_quota_available = $request->input('quota');
        $user->save();

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect('admin-users-show', ['id' => $id])
                ->with('message', 'Storage quota updated')
                ->with('messageType', 'success');
        }

    }
}
