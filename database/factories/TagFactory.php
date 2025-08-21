<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['programming', 'opsec', 'vulnerability', 'exploit', 'blue', 'red', 'purple']),
            'slug' => $this->faker->slug(),
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
