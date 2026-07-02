<?php

namespace App\Services\News;

use App\DataTransferObjects\ArticleData;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Support\Str;

class ArticleImporter
{
    /** @var array<string, int> */
    private array $sourceCache = [];

    /** @var array<string, int> */
    private array $categoryCache = [];

    /** @var array<string, int> */
    private array $authorCache = [];

    /**
     * Persist normalized articles, deduping on url_hash.
     *
     * @param  iterable<ArticleData>  $articles
     * @return int Number of rows written.
     */
    public function import(iterable $articles): int
    {
        $rows = [];

        foreach ($articles as $article) {
            $rows[$article->urlHash()] = $article->toUpsertRow([
                'source_id' => $this->resolveSource($article),
                'category_id' => $this->resolveCategory($article),
                'author_id' => $this->resolveAuthor($article),
            ]);
        }

        if ($rows === []) {
            return 0;
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            Article::upsert(
                $chunk,
                ['url_hash'],
                ['source_id', 'category_id', 'author_id', 'external_id', 'title',
                    'description', 'content', 'image_url', 'published_at', 'language', 'updated_at']
            );
        }

        return count($rows);
    }

    private function resolveSource(ArticleData $article): int
    {
        $slug = Str::slug($article->sourceName);

        return $this->sourceCache[$slug] ??= Source::firstOrCreate(
            ['slug' => $slug],
            ['name' => $article->sourceName, 'provider' => $article->provider, 'external_id' => $article->sourceExternalId]
        )->id;
    }

    private function resolveCategory(ArticleData $article): ?int
    {
        if (blank($article->categoryName)) {
            return null;
        }

        $slug = Str::slug($article->categoryName);

        return $this->categoryCache[$slug] ??= Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $article->categoryName]
        )->id;
    }

    private function resolveAuthor(ArticleData $article): ?int
    {
        if (blank($article->authorName)) {
            return null;
        }

        $slug = Str::slug($article->authorName);

        return $this->authorCache[$slug] ??= Author::firstOrCreate(
            ['slug' => $slug],
            ['name' => $article->authorName]
        )->id;
    }
}
