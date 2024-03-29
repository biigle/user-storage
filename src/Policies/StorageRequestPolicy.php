<?php

namespace Biigle\Modules\UserStorage\Policies;

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Policies\CachedPolicy;
use Biigle\Role;
use Biigle\User;
use DB;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorageRequestPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Intercept all checks.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before($user, $ability)
    {
        $except = ['update'];

        if ($user->can('sudo') && !in_array($ability, $except)) {
            return true;
        }
    }

    /**
     * Determine if the given user can create a new request.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user)
    {
        if (config('user_storage.maintenance_mode')) {
            return false;
        }

        return $user->role_id === Role::editorId() || $user->role_id === Role::adminId();
    }

    /**
     * Determine if the given request can be accessed by the user.
     *
     * @param  User  $user
     * @param  StorageRequest  $request
     * @return bool
     */
    public function access(User $user, StorageRequest $request)
    {
        return $user->id === $request->user_id;
    }

    /**
     * Determine if the given user can update the storage request.
     *
     * @param User $user
     * @param StorageRequest $request
     *
     * @return bool
     */
    public function update(User $user, StorageRequest $request)
    {
        if (config('user_storage.maintenance_mode')) {
            return $user->can('sudo') && $user->id === $request->user_id;
        }

        return $this->access($user, $request);
    }

    /**
     * Determine if the given user can approve the storage request.
     *
     * @param User $user
     * @param StorageRequest $request
     *
     * @return bool
     */
    public function approve(User $user, StorageRequest $request)
    {
        // Only global admins and reviewers can do this.
        return $user->canReview;
    }

    /**
     * Determine if the given user can reject the storage request.
     *
     * @param User $user
     * @param StorageRequest $request
     *
     * @return bool
     */
    public function reject(User $user, StorageRequest $request)
    {
        return $this->approve($user, $request);
    }

    /**
     * Determine if the given user can destroy the storage request.
     *
     * @param User $user
     * @param StorageRequest $request
     *
     * @return bool
     */
    public function destroy(User $user, StorageRequest $request)
    {
        return $this->access($user, $request);
    }
}
