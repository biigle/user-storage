<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Modules\UserStorage\Database\Factories\StorageRequestFactory;
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
        'expires_at' => 'date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'expires_at',
    ];

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
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return StorageRequestFactory::new();
    }
}
