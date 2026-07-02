<?php

namespace App\Jobs;

use App\Services\News\ArticleImporter;
use App\Services\News\NewsProviderRegistry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchProviderArticles implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public function __construct(public string $providerKey) {}

    public function handle(NewsProviderRegistry $registry, ArticleImporter $importer): void
    {
        $provider = $registry->get($this->providerKey);

        if ($provider === null || ! $provider->isConfigured()) {
            Log::warning("Skipping unconfigured news provider [{$this->providerKey}].");

            return;
        }

        $count = $importer->import($provider->fetch());

        Log::info("Imported {$count} articles from [{$this->providerKey}].");
    }

    public function uniqueId(): string
    {
        return $this->providerKey;
    }
}
