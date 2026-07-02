<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SourceResource::collection(Source::orderBy('name')->get());
    }
}
