<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'file_size' => $this->faker->randomNumber(),
            'mime_type' => $this->faker->mimeType(),
            'type' => $this->faker->word(),
            'download_count' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'post_id' => Post::factory(),
            'user_id' => User::factory(),
        ];
    }
}
