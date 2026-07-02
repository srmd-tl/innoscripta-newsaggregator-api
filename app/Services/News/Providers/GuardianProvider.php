<?php

namespace App\Services\News\Providers;

use App\Contracts\NewsProvider;
use App\DataTransferObjects\ArticleData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

class GuardianProvider implements NewsProvider
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
        return 'guardian';
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
            ->get('/search', array_filter([
                'api-key' => $this->config['key'],
                'page-size' => 50,
                'order-by' => 'newest',
                'show-fields' => 'trailText,bodyText,thumbnail,byline',
                'show-tags' => 'contributor',
                'from-date' => $since?->toDateString(),
            ]));

        if ($response->failed()) {
            Log::warning('Guardian fetch failed', ['status' => $response->status()]);

            return [];
        }

        foreach ($response->json('response.results', []) as $item) {
            if (blank($item['webUrl'] ?? null) || blank($item['webTitle'] ?? null)) {
                continue;
            }

            $fields = $item['fields'] ?? [];

            yield new ArticleData(
                provider: $this->key(),
                title: $item['webTitle'],
                url: $item['webUrl'],
                publishedAt: CarbonImmutable::parse($item['webPublicationDate'] ?? now()),
                sourceName: 'The Guardian',
                externalId: $item['id'] ?? null,
                description: $fields['trailText'] ?? null,
                content: $fields['bodyText'] ?? null,
                imageUrl: $fields['thumbnail'] ?? null,
                authorName: $fields['byline'] ?? null,
                categoryName: $item['sectionName'] ?? null,
                language: 'en',
            );
        }
    }
}
