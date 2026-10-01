<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'slug' => fake()->unique()->slug(4),
            'body' => fake()->paragraphs(3, true),
            'published_at' => null,
        ];
    }

    /** Post já publicado (ontem). */
    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()->subDay()]);
    }
}
