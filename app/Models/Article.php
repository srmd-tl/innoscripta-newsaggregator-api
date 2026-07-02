<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'source_id', 'category_id', 'author_id', 'provider', 'external_id',
        'url', 'url_hash', 'title', 'description', 'content', 'image_url',
        'published_at', 'language',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * @param  Builder<Article>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Filter by a relation's slug or id.
     *
     * @param  Builder<Article>  $query
     */
    public function scopeForRelation(Builder $query, string $relation, string|int|null $value): void
    {
        if (blank($value)) {
            return;
        }

        $column = is_numeric($value) ? 'id' : 'slug';

        $query->whereHas($relation, function (Builder $q) use ($column, $value): void {
            $q->where($column, $value);
        });
    }

    /**
     * @param  Builder<Article>  $query
     */
    public function scopePublishedBetween(Builder $query, ?string $from, ?string $to): void
    {
        if (filled($from)) {
            $query->whereDate('published_at', '>=', $from);
        }

        if (filled($to)) {
            $query->whereDate('published_at', '<=', $to);
        }
    }

    /**
     * Personalized feed: OR across the user's preferred sources/categories/authors.
     * With no preferences saved, returns the query unchanged (latest articles).
     *
     * @param  Builder<Article>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $sourceIds = $user->preferredSources()->pluck('sources.id');
        $categoryIds = $user->preferredCategories()->pluck('categories.id');
        $authorIds = $user->preferredAuthors()->pluck('authors.id');

        if ($sourceIds->isEmpty() && $categoryIds->isEmpty() && $authorIds->isEmpty()) {
            return;
        }

        $query->where(function (Builder $q) use ($sourceIds, $categoryIds, $authorIds): void {
            $q->whereIn('source_id', $sourceIds)
                ->orWhereIn('category_id', $categoryIds)
                ->orWhereIn('author_id', $authorIds);
        });
    }
}
