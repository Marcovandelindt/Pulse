<?php

declare(strict_types=1);

use App\Http\Requests\Health\StoreHealthEntryRequest;
use App\Models\HealthEntry;

it('shows the health index page', function () {
    $this->get(route('health.index'))->assertOk();
});

it('can store a health entry', function () {
    $this->post(route('health.store'), [
        'date' => today()->format('Y-m-d'),
        'steps' => 8500,
    ])->assertRedirect();

    $this->assertDatabaseHas('health_entries', ['steps' => 8500]);
});

it('requires date to be today or earlier', function () {
    $this->post(route('health.store'), [
        'date' => today()->addDay()->format('Y-m-d'),
        'steps' => 5000,
    ])->assertInvalid(['date']);
});

it('has a unique date constraint in the store request', function () {
    $rules = (new StoreHealthEntryRequest)->rules();

    expect($rules['date'])->toContain('unique:health_entries,date');
});

it('validates steps range', function (int $steps, bool $valid) {
    $response = $this->post(route('health.store'), [
        'date' => today()->format('Y-m-d'),
        'steps' => $steps,
    ]);

    $valid ? $response->assertValid('steps') : $response->assertInvalid('steps');
})->with([
    'zero is valid' => [0,      true],
    'normal steps' => [8500,   true],
    'negative invalid' => [-1,     false],
    'too high invalid' => [100001, false],
]);

it('can update an entry', function () {
    $entry = HealthEntry::factory()->create(['steps' => 5000]);

    $this->patch(route('health.update', $entry), [
        'steps' => 9000,
    ])->assertRedirect();

    expect($entry->fresh()->steps)->toBe(9000);
});

it('can delete an entry', function () {
    $entry = HealthEntry::factory()->create();

    $this->delete(route('health.destroy', $entry))->assertRedirect();

    $this->assertDatabaseMissing('health_entries', ['id' => $entry->id]);
});

it('detects when step goal is met', function () {
    $entry = HealthEntry::factory()->create(['steps' => 10000]);
    expect($entry->meetsStepGoal(10000))->toBeTrue();
});

it('detects when step goal is not met', function () {
    $entry = HealthEntry::factory()->create(['steps' => 9999]);
    expect($entry->meetsStepGoal(10000))->toBeFalse();
});
