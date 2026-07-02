<?php

namespace App\Http\Filters;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;

class ArticleFilter
{
    /**
     * Apply search/filter/sort params to an Article query.
     * Reused by the public article listing and the personalized feed (DRY).
     *
     * @param  Builder<Article>  $query
     * @param  array<string, mixed>  $params
     * @return Builder<Article>
     */
    public function apply(Builder $query, array $params): Builder
    {
        $query
            ->search($params['q'] ?? null)
            ->forRelation('source', $params['source'] ?? null)
            ->forRelation('category', $params['category'] ?? null)
            ->forRelation('author', $params['author'] ?? null)
            ->publishedBetween($params['date_from'] ?? null, $params['date_to'] ?? null);

        $direction = ($params['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->with(['source', 'category', 'author'])
            ->orderBy('published_at', $direction);
    }
}
