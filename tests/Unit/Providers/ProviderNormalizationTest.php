<?php

namespace Tests\Unit\Providers;

use App\Services\News\Providers\GuardianProvider;
use App\Services\News\Providers\NewsApiProvider;
use App\Services\News\Providers\NytProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderNormalizationTest extends TestCase
{
    private function http(array $stub): HttpFactory
    {
        $factory = new HttpFactory;
        $factory->fake($stub);

        return $factory;
    }

    public function test_newsapi_normalizes_payload(): void
    {
        $http = $this->http(['*' => Http::response([
            'articles' => [[
                'source' => ['id' => 'bbc', 'name' => 'BBC'],
                'author' => 'Jane Doe',
                'title' => 'Hello World',
                'description' => 'desc',
                'content' => 'body',
                'url' => 'https://example.com/a',
                'urlToImage' => 'https://example.com/a.jpg',
                'publishedAt' => '2026-06-01T10:00:00Z',
            ]],
        ])]);

        $provider = new NewsApiProvider($http, ['key' => 'x', 'base_url' => 'https://newsapi.org/v2', 'enabled' => true]);
        $articles = iterator_to_array($provider->fetch());

        $this->assertCount(1, $articles);
        $this->assertSame('Hello World', $articles[0]->title);
        $this->assertSame('BBC', $articles[0]->sourceName);
        $this->assertSame('Jane Doe', $articles[0]->authorName);
        $this->assertSame(hash('sha256', 'https://example.com/a'), $articles[0]->urlHash());
    }

    public function test_guardian_normalizes_payload(): void
    {
        $http = $this->http(['*' => Http::response([
            'response' => ['results' => [[
                'id' => 'world/1',
                'webTitle' => 'Guardian Story',
                'webUrl' => 'https://theguardian.com/1',
                'webPublicationDate' => '2026-06-02T08:00:00Z',
                'sectionName' => 'World',
                'fields' => ['trailText' => 't', 'bodyText' => 'b', 'thumbnail' => 'i.jpg', 'byline' => 'John Smith'],
            ]]],
        ])]);

        $provider = new GuardianProvider($http, ['key' => 'x', 'base_url' => 'https://content.guardianapis.com', 'enabled' => true]);
        $articles = iterator_to_array($provider->fetch());

        $this->assertSame('The Guardian', $articles[0]->sourceName);
        $this->assertSame('World', $articles[0]->categoryName);
        $this->assertSame('John Smith', $articles[0]->authorName);
    }

    public function test_nyt_normalizes_payload(): void
    {
        $http = $this->http(['*' => Http::response([
            'response' => ['docs' => [[
                '_id' => 'nyt://article/1',
                'web_url' => 'https://nytimes.com/1',
                'headline' => ['main' => 'NYT Story'],
                'abstract' => 'abs',
                'lead_paragraph' => 'lead',
                'pub_date' => '2026-06-03T12:00:00Z',
                'section_name' => 'Politics',
                'byline' => ['original' => 'By Alice Brown'],
                'multimedia' => ['default' => ['url' => 'https://static.nyt.com/i.jpg']],
            ]]],
        ])]);

        $provider = new NytProvider($http, ['key' => 'x', 'base_url' => 'https://api.nytimes.com/svc/search/v2', 'enabled' => true]);
        $articles = iterator_to_array($provider->fetch());

        $this->assertSame('NYT Story', $articles[0]->title);
        $this->assertSame('Politics', $articles[0]->categoryName);
        $this->assertSame('Alice Brown', $articles[0]->authorName);
    }

    public function test_unconfigured_provider_reports_not_configured(): void
    {
        $provider = new NewsApiProvider(new HttpFactory, ['key' => null, 'base_url' => 'x', 'enabled' => true]);

        $this->assertFalse($provider->isConfigured());
    }
}
