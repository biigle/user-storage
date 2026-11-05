<?php

namespace Biigle\Modules\UserStorage;

use Biigle\Modules\UserStorage\Database\Factories\StorageRequestFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Hidden;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;

#[Hidden(['received_chunks','total_chunks',])]
#[Fillable(['path','size','received_chunks','total_chunks','retry_count',])]
#[WithoutTimestamps]
class StorageRequestFile extends Model
{
    use HasFactory;

    

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'int',
        'received_chunks' => 'array',
        'total_chunks' => 'int',
        'retry_count' => 'int',
    ];


    

    /**
     * The request to which this file belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function request()
    {
        return $this->belongsTo(StorageRequest::class, 'storage_request_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return StorageRequestFileFactory::new();
    }
}
