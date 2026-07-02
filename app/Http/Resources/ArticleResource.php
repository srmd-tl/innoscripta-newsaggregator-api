<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'provider' => $this->provider,
            'language' => $this->language,
            'published_at' => $this->published_at?->toIso8601String(),
            'source' => new SourceResource($this->whenLoaded('source')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => new AuthorResource($this->whenLoaded('author')),
        ];
    }
}
