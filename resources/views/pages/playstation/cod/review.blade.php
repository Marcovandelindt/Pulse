<x-layouts.app title="Review Matches — CoD">

    <x-layout.page-header title="Review Matches">
        <x-slot:actions>
            <a href="{{ route('playstation.cod.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
             style="background: rgba(34,197,94,0.1); color: #4ade80; border: 1px solid rgba(34,197,94,0.2);">
            {{ session('success') }}
        </div>
    @endif

    @if($matches->isEmpty())
        <x-ui.empty-state
            title="Nothing to review"
            description="All matches have been confirmed."
        />
    @else
        <div class="cod-review-list">
            @foreach($matches as $match)
                <div class="cod-review-card">

                    {{-- Screenshot --}}
                    <div class="cod-review-card__screenshot">
                        @if($match->screenshots->isNotEmpty())
                            <img
                                src="{{ route('playstation.cod.screenshots.show', $match->screenshots->first()) }}"
                                alt="Match screenshot"
                                class="cod-review-card__img"
                            >
                        @else
                            <div class="cod-review-card__no-screenshot">No screenshot</div>
                        @endif
                    </div>

                    {{-- Edit form --}}
                    <form
                        action="{{ route('playstation.cod.review.update', $match) }}"
                        method="POST"
                        class="cod-review-card__form"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="cod-review-card__grid">

                            <div class="form-group">
                                <label class="form-label">Map</label>
                                <input type="text" name="map" value="{{ old('map', $match->map->name) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Mode</label>
                                <input type="text" name="mode" value="{{ old('mode', $match->mode->name) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Result</label>
                                <select name="result" class="form-input" required>
                                    <option value="victory" @selected(old('result', $match->result->value) === 'victory')>Victory</option>
                                    <option value="defeat"  @selected(old('result', $match->result->value) === 'defeat')>Defeat</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Team score</label>
                                <input type="number" name="team_score" value="{{ old('team_score', $match->team_score) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Opponent score</label>
                                <input type="number" name="opponent_score" value="{{ old('opponent_score', $match->opponent_score) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Score</label>
                                <input type="number" name="score" value="{{ old('score', $match->score) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Kills</label>
                                <input type="number" name="kills" value="{{ old('kills', $match->kills) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Deaths</label>
                                <input type="number" name="deaths" value="{{ old('deaths', $match->deaths) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">K/D Ratio</label>
                                <input type="number" name="kd_ratio" value="{{ old('kd_ratio', $match->kd_ratio) }}" class="form-input" step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Objective score</label>
                                <input type="number" name="objective_score" value="{{ old('objective_score', $match->objective_score) }}" class="form-input" min="0">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Damage</label>
                                <input type="number" name="damage" value="{{ old('damage', $match->damage) }}" class="form-input" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Estimated date</label>
                                <input type="date" name="estimated_date" value="{{ old('estimated_date', $match->estimated_date->format('Y-m-d')) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Weapon</label>
                                <input type="text" name="weapon_name" value="{{ old('weapon_name', $match->topWeapon?->name) }}" class="form-input" placeholder="e.g. Kastov 762">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Weapon category</label>
                                <input type="text" name="weapon_category" value="{{ old('weapon_category', $match->topWeapon?->category) }}" class="form-input" placeholder="e.g. Assault Rifle">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Weapon accuracy (%)</label>
                                <input type="number" name="weapon_accuracy" value="{{ old('weapon_accuracy', $match->weapon_accuracy) }}" class="form-input" step="0.1" min="0" max="100">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Weapon kills</label>
                                <input type="number" name="weapon_kills" value="{{ old('weapon_kills', $match->weapon_kills) }}" class="form-input" min="0">
                            </div>

                        </div>

                        <div class="cod-review-card__actions">
                            <button type="submit" class="btn btn--primary btn--sm">Confirm</button>

                            <form action="{{ route('playstation.cod.review.destroy', $match) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Reject and delete this match?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">Reject</button>
                            </form>
                        </div>

                    </form>

                </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
