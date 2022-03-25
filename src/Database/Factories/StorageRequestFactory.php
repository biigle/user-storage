<?php

namespace Biigle\Modules\UserStorage\Database\Factories;

use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StorageRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'files' => [],
            'expires_at' => null,
            'submitted_at' => null,
        ];
    }
}
