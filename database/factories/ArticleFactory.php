<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = fake()->unique()->url();

        return [
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
            'author_id' => Author::factory(),
            'provider' => fake()->randomElement(['newsapi', 'guardian', 'nyt']),
            'external_id' => fake()->uuid(),
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'image_url' => fake()->imageUrl(),
            'published_at' => fake()->dateTimeBetween('-1 month'),
            'language' => 'en',
        ];
    }
}
