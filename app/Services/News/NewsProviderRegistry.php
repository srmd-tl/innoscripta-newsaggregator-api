<?php

namespace App\Services\News;

use App\Contracts\NewsProvider;

class NewsProviderRegistry
{
    /** @var array<string, NewsProvider> */
    private array $providers = [];

    /**
     * @param  iterable<NewsProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    /**
     * @return array<string, NewsProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Only providers that are properly configured (API key present).
     *
     * @return array<string, NewsProvider>
     */
    public function configured(): array
    {
        return array_filter(
            $this->providers,
            fn (NewsProvider $provider): bool => $provider->isConfigured()
        );
    }

    public function get(string $key): ?NewsProvider
    {
        return $this->providers[$key] ?? null;
    }
}
