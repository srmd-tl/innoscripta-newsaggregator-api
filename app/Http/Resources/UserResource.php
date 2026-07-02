<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'preferences' => [
                'sources' => SourceResource::collection($this->whenLoaded('preferredSources')),
                'categories' => CategoryResource::collection($this->whenLoaded('preferredCategories')),
                'authors' => AuthorResource::collection($this->whenLoaded('preferredAuthors')),
            ],
        ];
    }
}
