<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_paginated_articles(): void
    {
        Article::factory()->count(3)->create();

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'source', 'category', 'author']], 'meta', 'links']);
    }

    public function test_searches_by_keyword(): void
    {
        Article::factory()->create(['title' => 'Climate summit opens']);
        Article::factory()->create(['title' => 'Sports roundup']);

        $this->getJson('/api/v1/articles?q=climate')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Climate summit opens');
    }

    public function test_filters_by_source_slug(): void
    {
        $source = Source::factory()->create(['slug' => 'the-guardian']);
        Article::factory()->create(['source_id' => $source->id]);
        Article::factory()->create();

        $this->getJson('/api/v1/articles?source=the-guardian')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filters_by_date_range(): void
    {
        Article::factory()->create(['published_at' => '2026-01-01 10:00:00']);
        Article::factory()->create(['published_at' => '2026-06-01 10:00:00']);

        $this->getJson('/api/v1/articles?date_from=2026-05-01&date_to=2026-07-01')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_shows_single_article(): void
    {
        $article = Article::factory()->create();

        $this->getJson("/api/v1/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $article->id);
    }

    public function test_returns_404_for_missing_article(): void
    {
        $this->getJson('/api/v1/articles/999')->assertNotFound();
    }

    public function test_rejects_invalid_filters(): void
    {
        $this->getJson('/api/v1/articles?per_page=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_lists_sources_and_categories(): void
    {
        Source::factory()->create();
        Category::factory()->create();

        $this->getJson('/api/v1/sources')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');
    }
}
