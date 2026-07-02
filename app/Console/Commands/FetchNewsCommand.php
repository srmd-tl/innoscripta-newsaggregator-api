<?php

namespace App\Console\Commands;

use App\Jobs\FetchProviderArticles;
use App\Services\News\ArticleImporter;
use App\Services\News\NewsProviderRegistry;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch {--provider= : Fetch a single provider by key} {--sync : Run inline instead of dispatching to the queue}';

    protected $description = 'Fetch and store articles from the configured news providers';

    public function handle(NewsProviderRegistry $registry, ArticleImporter $importer): int
    {
        $providers = $registry->configured();

        if ($key = $this->option('provider')) {
            $providers = array_filter($providers, fn ($p) => $p->key() === $key);

            if ($providers === []) {
                $this->error("Provider [{$key}] is not registered or not configured.");

                return self::FAILURE;
            }
        }

        if ($providers === []) {
            $this->warn('No configured news providers. Set API keys in your .env file.');

            return self::SUCCESS;
        }

        foreach ($providers as $provider) {
            if ($this->option('sync')) {
                $count = $importer->import($provider->fetch());
                $this->info("[{$provider->key()}] imported {$count} articles.");
            } else {
                FetchProviderArticles::dispatch($provider->key());
                $this->info("[{$provider->key()}] fetch job dispatched.");
            }
        }

        return self::SUCCESS;
    }
}
