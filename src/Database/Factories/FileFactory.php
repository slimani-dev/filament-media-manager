<?php

namespace Slimani\MediaManager\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Slimani\MediaManager\Models\File;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'uploaded_by_user_id' => User::factory(),
            'name' => $this->faker->word(),
            'size' => $this->faker->randomNumber(5),
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
        ];
    }
}
