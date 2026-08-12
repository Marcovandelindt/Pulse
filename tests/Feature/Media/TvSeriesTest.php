<?php

declare(strict_types=1);

use App\Models\EpisodeWatch;
use App\Models\TvEpisode;
use App\Models\TvSeason;
use App\Models\TvSeries;
use Illuminate\Support\Facades\Http;

it('shows the tv series index page', function () {
    $this->get(route('tv.index'))->assertOk();
});

it('shows the tv index with existing series', function () {
    TvSeries::factory()->count(2)->create();

    $this->get(route('tv.index'))->assertOk();
});

it('shows the tv series detail page', function () {
    $series = TvSeries::factory()->create();

    $this->get(route('tv.show', $series))->assertOk();
});

it('can add a series from tmdb', function () {
    $seriesData = [
        'id' => 1396,
        'name' => 'Breaking Bad',
        'original_name' => 'Breaking Bad',
        'overview' => 'A high school chemistry teacher…',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => null,
        'first_air_date' => '2008-01-20',
        'last_air_date' => '2013-09-29',
        'vote_average' => 9.5,
        'genres' => [['id' => 18, 'name' => 'Drama']],
        'status' => 'Ended',
        'original_language' => 'en',
        'number_of_seasons' => 1,
        'number_of_episodes' => 2,
    ];

    Http::fake(function ($request) use ($seriesData) {
        $url = $request->url();

        if (str_contains($url, '/aggregate_credits')) {
            return Http::response(['cast' => [], 'crew' => []], 200);
        }

        if (str_contains($url, '/season/')) {
            return Http::response([
                'id' => 9999,
                'name' => 'Season 1',
                'overview' => null,
                'poster_path' => null,
                'air_date' => '2008-01-20',
                'season_number' => 1,
                'episodes' => [
                    ['id' => 1, 'episode_number' => 1, 'name' => 'Pilot', 'overview' => null, 'air_date' => '2008-01-20', 'runtime' => 58, 'vote_average' => 7.7],
                    ['id' => 2, 'episode_number' => 2, 'name' => "Cat's in the Bag", 'overview' => null, 'air_date' => '2008-01-27', 'runtime' => 48, 'vote_average' => 8.2],
                ],
            ], 200);
        }

        return Http::response($seriesData, 200);
    });

    $this->postJson(route('tv.store'), ['tmdb_id' => 1396])
        ->assertOk()
        ->assertJsonPath('added', true)
        ->assertJsonPath('name', 'Breaking Bad');

    $this->assertDatabaseHas('tv_series', ['tmdb_id' => 1396, 'name' => 'Breaking Bad']);
    $this->assertDatabaseCount('tv_episodes', 2);
});

it('requires tmdb_id to add a series', function () {
    $this->postJson(route('tv.store'), [])
        ->assertJsonValidationErrors(['tmdb_id']);
});

it('can mark an episode as watched', function () {
    $series = TvSeries::factory()->create(['number_of_episodes' => 1]);
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id, 'episode_count' => 1]);
    $episode = TvEpisode::factory()->create(['tv_season_id' => $season->id]);

    $this->postJson(route('tv.episodes.watches.store', $episode), [
        'watched_at' => today()->format('Y-m-d'),
    ])->assertOk()->assertJsonStructure(['watch_id', 'episodes_watched', 'completion_percentage']);

    $this->assertDatabaseHas('episode_watches', ['tv_episode_id' => $episode->id]);
});

it('updates series progress when an episode is marked watched', function () {
    $series = TvSeries::factory()->create(['number_of_episodes' => 1]);
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id, 'episode_count' => 1]);
    $episode = TvEpisode::factory()->create(['tv_season_id' => $season->id]);

    $this->postJson(route('tv.episodes.watches.store', $episode), [
        'watched_at' => today()->format('Y-m-d'),
    ])->assertOk();

    $fresh = $series->fresh();
    expect($fresh->episodes_watched)->toBe(1);
    expect((float) $fresh->completion_percentage)->toBe(100.0);
});

it('can unmark a watched episode', function () {
    $series = TvSeries::factory()->create(['number_of_episodes' => 1]);
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id, 'episode_count' => 1]);
    $episode = TvEpisode::factory()->create(['tv_season_id' => $season->id]);
    $watch = EpisodeWatch::factory()->create(['tv_episode_id' => $episode->id]);

    $this->deleteJson(route('tv.watches.destroy', $watch))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertModelMissing($watch);
});

it('can bulk mark all series episodes as watched', function () {
    $series = TvSeries::factory()->create(['number_of_episodes' => 3]);
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id, 'episode_count' => 3]);
    TvEpisode::factory()->count(3)
        ->sequence(fn ($seq) => ['episode_number' => $seq->index + 1, 'name' => 'Episode '.($seq->index + 1)])
        ->create(['tv_season_id' => $season->id]);

    $this->postJson(route('tv.watches.bulk', $series), [
        'watched_at' => today()->format('Y-m-d'),
    ])->assertOk()->assertJsonStructure(['episodes_watched', 'completion_percentage']);

    expect(EpisodeWatch::count())->toBe(3);
});

it('validates rating between 1 and 10 for episodes', function () {
    $series = TvSeries::factory()->create();
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id]);
    $episode = TvEpisode::factory()->create(['tv_season_id' => $season->id]);

    $this->postJson(route('tv.episodes.watches.store', $episode), ['rating' => 11])
        ->assertJsonValidationErrors(['rating']);
});

it('can delete a series', function () {
    $series = TvSeries::factory()->create();

    $this->deleteJson(route('tv.destroy', $series))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertModelMissing($series);
});

it('cascades episode watch deletion when a series is deleted', function () {
    $series = TvSeries::factory()->create(['number_of_episodes' => 1]);
    $season = TvSeason::factory()->create(['tv_series_id' => $series->id, 'episode_count' => 1]);
    $episode = TvEpisode::factory()->create(['tv_season_id' => $season->id]);
    $watch = EpisodeWatch::factory()->create(['tv_episode_id' => $episode->id]);

    $this->deleteJson(route('tv.destroy', $series))->assertOk();

    $this->assertModelMissing($watch);
    $this->assertModelMissing($episode);
    $this->assertModelMissing($season);
});
