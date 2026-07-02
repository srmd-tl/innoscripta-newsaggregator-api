<?php

namespace App\Services\News\Providers;

use App\Contracts\NewsProvider;
use App\DataTransferObjects\ArticleData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

class NewsApiProvider implements NewsProvider
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
        return 'newsapi';
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
            ->get('/everything', array_filter([
                'apiKey' => $this->config['key'],
                'q' => 'news',
                'language' => 'en',
                'pageSize' => 100,
                'sortBy' => 'publishedAt',
                'from' => $since?->toIso8601String(),
            ]));

        if ($response->failed()) {
            Log::warning('NewsAPI fetch failed', ['status' => $response->status()]);

            return [];
        }

        foreach ($response->json('articles', []) as $item) {
            if (blank($item['url'] ?? null) || blank($item['title'] ?? null)) {
                continue;
            }

            yield new ArticleData(
                provider: $this->key(),
                title: $item['title'],
                url: $item['url'],
                publishedAt: CarbonImmutable::parse($item['publishedAt'] ?? now()),
                sourceName: $item['source']['name'] ?? 'NewsAPI',
                externalId: $item['source']['id'] ?? null,
                description: $item['description'] ?? null,
                content: $item['content'] ?? null,
                imageUrl: $item['urlToImage'] ?? null,
                authorName: $item['author'] ?? null,
                sourceExternalId: $item['source']['id'] ?? null,
                language: 'en',
            );
        }
    }
}
