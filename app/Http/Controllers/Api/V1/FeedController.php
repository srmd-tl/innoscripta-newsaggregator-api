<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\ArticleFilter;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function index(ArticleIndexRequest $request, ArticleFilter $filter): AnonymousResourceCollection
    {
        $query = Article::query()->forUser($request->user());

        $articles = $filter
            ->apply($query, $request->validated())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ArticleResource::collection($articles);
    }
}
