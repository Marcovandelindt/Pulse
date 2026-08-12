<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\MovieWatch;
use Illuminate\Support\Facades\Http;

it('shows the movies index page', function () {
    $this->get(route('movies.index'))->assertOk();
});

it('shows the movies index with existing movies', function () {
    Movie::factory()->count(3)->create();

    $this->get(route('movies.index'))->assertOk();
});

it('shows the movie detail page', function () {
    $movie = Movie::factory()->create();

    $this->get(route('movies.show', $movie))->assertOk();
});

it('can add a movie from tmdb', function () {
    Http::fake(fn () => Http::response([
        'id' => 550,
        'title' => 'Fight Club',
        'original_title' => 'Fight Club',
        'overview' => 'An insomniac office worker…',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'release_date' => '1999-10-15',
        'runtime' => 139,
        'vote_average' => 8.4,
        'genres' => [['id' => 18, 'name' => 'Drama']],
        'original_language' => 'en',
        'credits' => ['cast' => [], 'crew' => []],
    ], 200));

    $this->postJson(route('movies.store'), ['tmdb_id' => 550])
        ->assertOk()
        ->assertJsonPath('added', true)
        ->assertJsonPath('title', 'Fight Club');

    $this->assertDatabaseHas('movies', ['tmdb_id' => 550, 'title' => 'Fight Club']);
});

it('requires tmdb_id to add a movie', function () {
    $this->postJson(route('movies.store'), [])
        ->assertJsonValidationErrors(['tmdb_id']);
});

it('can mark a movie as watched', function () {
    $movie = Movie::factory()->create();

    $this->postJson(route('movies.watches.store', $movie), [
        'watched_at' => today()->format('Y-m-d'),
        'rating' => 8,
    ])->assertOk();

    $this->assertDatabaseHas('movie_watches', [
        'movie_id' => $movie->id,
        'rating' => 8,
    ]);
});

it('can mark a movie as watched with year only', function () {
    $movie = Movie::factory()->create();

    $this->postJson(route('movies.watches.store', $movie), [
        'year_only' => true,
    ])->assertOk();

    $this->assertDatabaseHas('movie_watches', [
        'movie_id' => $movie->id,
        'year_only' => true,
    ]);
});

it('validates rating between 1 and 10', function () {
    $movie = Movie::factory()->create();

    $this->postJson(route('movies.watches.store', $movie), ['rating' => 11])
        ->assertJsonValidationErrors(['rating']);

    $this->postJson(route('movies.watches.store', $movie), ['rating' => 0])
        ->assertJsonValidationErrors(['rating']);
});

it('rejects a future watched_at date', function () {
    $movie = Movie::factory()->create();

    $this->postJson(route('movies.watches.store', $movie), [
        'watched_at' => now()->addDay()->format('Y-m-d'),
    ])->assertJsonValidationErrors(['watched_at']);
});

it('can delete a movie watch', function () {
    $watch = MovieWatch::factory()->create();

    $this->deleteJson(route('movies.watches.destroy', $watch))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertModelMissing($watch);
});

it('can delete a movie', function () {
    $movie = Movie::factory()->create();

    $this->deleteJson(route('movies.destroy', $movie))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertModelMissing($movie);
});

it('cascades watch deletion when a movie is deleted', function () {
    $watch = MovieWatch::factory()->create();
    $movie = $watch->movie;

    $this->deleteJson(route('movies.destroy', $movie))->assertOk();

    $this->assertModelMissing($watch);
});
