<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($this->loadPreferences($request));
    }

    public function update(UpdatePreferencesRequest $request): UserResource
    {
        $user = $request->user();

        $user->preferredSources()->sync($request->validated('sources', []));
        $user->preferredCategories()->sync($request->validated('categories', []));
        $user->preferredAuthors()->sync($request->validated('authors', []));

        return new UserResource($this->loadPreferences($request));
    }

    private function loadPreferences(Request $request): User
    {
        return $request->user()->load(['preferredSources', 'preferredCategories', 'preferredAuthors']);
    }
}
