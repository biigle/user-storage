<?php

namespace Biigle\Modules\UserStorage;

use Biigle\User as BaseModel;
use Cache;

class User extends BaseModel
{
    /**
     * Converts a regular user to an user storage user.
     *
     * @param BaseModel $user Regular user instance
     *
     * @return User
     */
    public static function convert(BaseModel $model)
    {
        $instance = new static;
        $instance->setRawAttributes($model->attributes);
        $instance->exists = $model->exists;

        return $instance->setRelations($model->relations);
    }

    /**
     * Get the available storage quota of the user in bytes.
     *
     * @return int
     */
    public function getStorageQuotaAvailableAttribute()
    {
        return $this->getJsonAttr('storage_quota_available', config('user_storage.user_quota'));
    }

    /**
     * Set the allowed user storage quota.
     *
     * @@param int|null $value
     */
    public function setStorageQuotaAvailableAttribute($value)
    {
        $this->setJsonAttr('storage_quota_available', max(0, $value));
    }

    /**
     * Get the used storage quota of the user in bytes.
     *
     * @return int
     */
    public function getStorageQuotaUsedAttribute()
    {
        $key = "user-{$this->id}-storage-quota-used";

        return Cache::store('array')->remember($key, null, function () {
            return (int) StorageRequestFile::join('storage_requests', 'storage_requests.id', '=', 'storage_request_files.storage_request_id')
                ->where('storage_requests.user_id', $this->id)
                ->sum('storage_request_files.size');
        });
    }

    /**
     * Get the remaining storage quota of the user in bytes.
     *
     * @return int
     */
    public function getStorageQuotaRemainingAttribute()
    {
        return $this->storage_quota_available - $this->storage_quota_used;
    }
}
