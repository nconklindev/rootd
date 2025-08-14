<?php

namespace Database\Factories;

use App\Enum\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->slug(),
            'content' => $this->faker->sentences(10, true),
            'excerpt' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(PostType::cases())->value,
            'status' => $this->faker->randomElement(['draft', 'published']),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
