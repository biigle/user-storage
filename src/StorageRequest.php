<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Modules\UserStorage\Database\Factories\StorageRequestFactory;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestDirectory;
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'created_at_for_humans',
        'expires_at_for_humans',
        'files_count',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($request) {
            if ($request->files()->exists()) {
                DeleteStorageRequestDirectory::dispatch($request);
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
     * The files belonging to this storage request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files()
    {
        return $this->hasMany(StorageRequestFile::class);
    }

    /**
     * Get the created_at_for_humans attribute
     */
    public function getCreatedAtForHumansAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the expires_at_for_humans attribute
     */
    public function getExpiresAtForHumansAttribute()
    {
        return $this->expires_at ? $this->expires_at->diffForHumans() : null;
    }

    /**
     * Get the files_count attribute
     */
    public function getFilesCountAttribute()
    {
        return $this->files()->count();
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
