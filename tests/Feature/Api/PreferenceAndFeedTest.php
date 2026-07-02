<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenceAndFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_and_view_preferences(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $source = Source::factory()->create();
        $category = Category::factory()->create();

        $this->putJson('/api/v1/preferences', [
            'sources' => [$source->id],
            'categories' => [$category->id],
        ])->assertOk()
            ->assertJsonPath('data.preferences.sources.0.id', $source->id);

        $this->assertDatabaseHas('source_user', ['user_id' => $user->id, 'source_id' => $source->id]);
    }

    public function test_preferences_reject_unknown_ids(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/preferences', ['sources' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sources.0');
    }

    public function test_feed_returns_only_preferred_articles(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $preferred = Source::factory()->create();
        $other = Source::factory()->create();
        $user->preferredSources()->attach($preferred);

        Article::factory()->create(['source_id' => $preferred->id]);
        Article::factory()->create(['source_id' => $other->id]);

        $this->getJson('/api/v1/feed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source.id', $preferred->id);
    }

    public function test_feed_falls_back_to_latest_when_no_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Article::factory()->count(2)->create();

        $this->getJson('/api/v1/feed')->assertOk()->assertJsonCount(2, 'data');
    }
}
