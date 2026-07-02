<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.newsapi.key' => 'test',
            'services.guardian.key' => 'test',
            'services.nyt.key' => 'test',
        ]);
    }

    private function fakeProviders(): void
    {
        Http::fake([
            'newsapi.org/*' => Http::response(['articles' => [[
                'source' => ['name' => 'BBC'], 'author' => 'Jane', 'title' => 'A',
                'url' => 'https://ex.com/a', 'publishedAt' => '2026-06-01T10:00:00Z',
            ]]]),
            'content.guardianapis.com/*' => Http::response(['response' => ['results' => [[
                'id' => 'g/1', 'webTitle' => 'B', 'webUrl' => 'https://ex.com/b',
                'webPublicationDate' => '2026-06-02T10:00:00Z', 'sectionName' => 'World', 'fields' => [],
            ]]]]),
            'api.nytimes.com/*' => Http::response(['response' => ['docs' => [[
                '_id' => 'n/1', 'web_url' => 'https://ex.com/c', 'headline' => ['main' => 'C'],
                'pub_date' => '2026-06-03T10:00:00Z', 'section_name' => 'Politics', 'byline' => ['original' => 'By Bob'],
            ]]]]),
        ]);
    }

    public function test_fetch_stores_articles_from_all_providers(): void
    {
        $this->fakeProviders();

        $this->artisan('news:fetch --sync')->assertSuccessful();

        $this->assertSame(3, Article::count());
        $this->assertDatabaseHas('sources', ['name' => 'BBC']);
        $this->assertDatabaseHas('categories', ['name' => 'World']);
        $this->assertDatabaseHas('authors', ['name' => 'Jane']);
    }

    public function test_refetch_does_not_create_duplicates(): void
    {
        $this->fakeProviders();

        $this->artisan('news:fetch --sync')->assertSuccessful();
        $this->artisan('news:fetch --sync')->assertSuccessful();

        $this->assertSame(3, Article::count());
    }

    public function test_unconfigured_providers_are_skipped(): void
    {
        config(['services.newsapi.key' => null, 'services.guardian.key' => null, 'services.nyt.key' => null]);

        $this->artisan('news:fetch --sync')
            ->expectsOutputToContain('No configured news providers.')
            ->assertSuccessful();

        $this->assertSame(0, Article::count());
    }
}
