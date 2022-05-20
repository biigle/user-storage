<?php

namespace Biigle\Modules\UserStorage\Database\Factories;

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageRequestFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StorageRequestFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'path' => 'my/file.jpg',
            'storage_request_id' => StorageRequest::factory(),
            'size' => 123,
            'received_chunks' => null,
            'total_chunks' => null,
        ];
    }
}
