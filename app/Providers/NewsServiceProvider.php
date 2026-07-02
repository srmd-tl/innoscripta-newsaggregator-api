<?php

namespace App\Providers;

use App\Services\News\NewsProviderRegistry;
use App\Services\News\Providers\GuardianProvider;
use App\Services\News\Providers\NewsApiProvider;
use App\Services\News\Providers\NytProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsApiProvider::class, fn ($app) => new NewsApiProvider($app->make(HttpFactory::class), config('services.newsapi')));
        $this->app->singleton(GuardianProvider::class, fn ($app) => new GuardianProvider($app->make(HttpFactory::class), config('services.guardian')));
        $this->app->singleton(NytProvider::class, fn ($app) => new NytProvider($app->make(HttpFactory::class), config('services.nyt')));

        // Register every provider here — this is the ONLY change needed to add a source.
        $this->app->tag([
            NewsApiProvider::class,
            GuardianProvider::class,
            NytProvider::class,
        ], 'news.providers');

        $this->app->singleton(
            NewsProviderRegistry::class,
            fn ($app) => new NewsProviderRegistry($app->tagged('news.providers'))
        );
    }
}
