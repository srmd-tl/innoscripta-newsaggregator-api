<?php

namespace App\Contracts;

use App\DataTransferObjects\ArticleData;
use Carbon\CarbonInterface;

interface NewsProvider
{
    /**
     * Unique provider key, e.g. "newsapi".
     */
    public function key(): string;

    /**
     * Whether the provider has the configuration (API key) it needs to run.
     */
    public function isConfigured(): bool;

    /**
     * Fetch and normalize articles into ArticleData DTOs.
     *
     * @return iterable<ArticleData>
     */
    public function fetch(?CarbonInterface $since = null): iterable;
}
