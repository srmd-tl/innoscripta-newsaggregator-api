<?php

namespace App\Services\News\Providers;

use App\Contracts\NewsProvider;
use App\DataTransferObjects\ArticleData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

class NytProvider implements NewsProvider
{
    /**
     * @param  array{key:?string, base_url:string, enabled:bool}  $config
     */
    public function __construct(
        private HttpFactory $http,
        private array $config,
    ) {}

    public function key(): string
    {
        return 'nyt';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['key']) && ($this->config['enabled'] ?? true);
    }

    public function fetch(?CarbonInterface $since = null): iterable
    {
        $response = $this->http
            ->baseUrl($this->config['base_url'])
            ->timeout(30)
            ->get('/articlesearch.json', array_filter([
                'api-key' => $this->config['key'],
                'sort' => 'newest',
                'begin_date' => $since?->format('Ymd'),
            ]));

        if ($response->failed()) {
            Log::warning('NYT fetch failed', ['status' => $response->status()]);

            return [];
        }

        foreach ($response->json('response.docs', []) as $item) {
            $url = $item['web_url'] ?? null;
            $title = $item['headline']['main'] ?? null;

            if (blank($url) || blank($title)) {
                continue;
            }

            yield new ArticleData(
                provider: $this->key(),
                title: $title,
                url: $url,
                publishedAt: CarbonImmutable::parse($item['pub_date'] ?? now()),
                sourceName: $item['source'] ?? 'The New York Times',
                externalId: $item['_id'] ?? null,
                description: $item['abstract'] ?? null,
                content: $item['lead_paragraph'] ?? null,
                imageUrl: $this->imageUrl($item),
                authorName: $this->author($item),
                categoryName: $item['section_name'] ?? null,
                language: 'en',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function imageUrl(array $item): ?string
    {
        $path = $item['multimedia']['default']['url'] ?? ($item['multimedia'][0]['url'] ?? null);

        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : "https://www.nytimes.com/{$path}";
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function author(array $item): ?string
    {
        $byline = $item['byline']['original'] ?? null;

        return $byline ? trim(preg_replace('/^By /i', '', $byline)) : null;
    }
}
