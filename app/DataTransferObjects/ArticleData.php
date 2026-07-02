<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

readonly class ArticleData
{
    public function __construct(
        public string $provider,
        public string $title,
        public string $url,
        public CarbonImmutable $publishedAt,
        public string $sourceName,
        public ?string $externalId = null,
        public ?string $description = null,
        public ?string $content = null,
        public ?string $imageUrl = null,
        public ?string $authorName = null,
        public ?string $sourceExternalId = null,
        public ?string $categoryName = null,
        public ?string $language = null,
    ) {}

    /**
     * Canonical dedupe key.
     */
    public function urlHash(): string
    {
        return hash('sha256', $this->url);
    }

    /**
     * Build the DB row for Article::upsert().
     *
     * @param  array{source_id:int, category_id:?int, author_id:?int}  $ids
     * @return array<string, mixed>
     */
    public function toUpsertRow(array $ids): array
    {
        return [
            'source_id' => $ids['source_id'],
            'category_id' => $ids['category_id'],
            'author_id' => $ids['author_id'],
            'provider' => $this->provider,
            'external_id' => $this->externalId,
            'url' => $this->url,
            'url_hash' => $this->urlHash(),
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'image_url' => $this->imageUrl,
            'published_at' => $this->publishedAt,
            'language' => $this->language,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }
}
