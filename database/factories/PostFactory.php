<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(6),
            'category' => 'news',
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'published_at' => now()->subDay(),
        ];
    }
}
