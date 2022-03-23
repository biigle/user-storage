<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Modules\UserStorage\Database\Factories\StorageRequestFactory;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFiles;
use Biigle\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'expires_at',
        'submitted_at',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($request) {
            if (!empty($request->files)) {
                DeleteStorageRequestFiles::dispatch($request);
            }
        });
    }

    /**
     * The user who created this storage request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the files attribute.
     *
     * @param array $value
     */
    public function setFilesAttribute(array $value)
    {
        $this->attributes['files'] = implode(',', $value);
    }

    /**
     * Get the files attribute.
     */
    public function getFilesAttribute()
    {
        return array_filter(explode(',', $this->attributes['files']));
    }

    /**
     * Get the path to the directory or file of this request in the pending storage disk.
     *
     * @param string|null $value
     *
     * @return string
     */
    public function getPendingPath($value = null)
    {
        $path = "request-{$this->id}";

        if (!is_null($value)) {
            $path .= "/{$value}";
        }

        return $path;
    }

    /**
     * Get the path to the directory or file of this request in the storage disk.
     *
     * @param string|null $value
     *
     * @return string
     */
    public function getStoragePath($value = null)
    {
        $path = "user-{$this->user_id}";

        if (!is_null($value)) {
            $path .= "/{$value}";
        }

        return $path;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return StorageRequestFactory::new();
    }
}
