<x-layouts.app title="Settings">

    <x-layout.page-header title="Settings" />

    @if (session('success'))
        <div class="settings-notice settings-notice--success">{{ session('success') }}</div>
    @endif

    <div class="settings-page">

        {{-- Weather --}}
        <section class="settings-card">
            <div class="settings-card__header">
                <div class="settings-card__title-group">
                    <h2 class="settings-card__title">Weather</h2>
                    <p class="settings-card__desc">Location used for the weather widget on your dashboard.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.update') }}" class="settings-card__body">
                @csrf

                <div class="settings-field">
                    <label class="settings-field__label" for="weather_city">City name</label>
                    <input
                        id="weather_city"
                        type="text"
                        name="weather_city"
                        class="form-input {{ $errors->has('weather_city') ? 'form-input--error' : '' }}"
                        value="{{ old('weather_city', $weatherCity) }}"
                        placeholder="Amsterdam"
                        maxlength="100"
                        required
                    >
                    <x-form.error name="weather_city" />
                </div>

                <div class="settings-field-row">
                    <div class="settings-field">
                        <label class="settings-field__label" for="weather_lat">Latitude</label>
                        <input
                            id="weather_lat"
                            type="number"
                            name="weather_lat"
                            class="form-input {{ $errors->has('weather_lat') ? 'form-input--error' : '' }}"
                            value="{{ old('weather_lat', $weatherLat) }}"
                            step="any"
                            min="-90"
                            max="90"
                            placeholder="52.3676"
                            required
                        >
                        <x-form.error name="weather_lat" />
                    </div>

                    <div class="settings-field">
                        <label class="settings-field__label" for="weather_lon">Longitude</label>
                        <input
                            id="weather_lon"
                            type="number"
                            name="weather_lon"
                            class="form-input {{ $errors->has('weather_lon') ? 'form-input--error' : '' }}"
                            value="{{ old('weather_lon', $weatherLon) }}"
                            step="any"
                            min="-180"
                            max="180"
                            placeholder="4.9041"
                            required
                        >
                        <x-form.error name="weather_lon" />
                    </div>
                </div>

                <div class="settings-card__footer">
                    <button type="submit" class="btn btn--primary btn--sm">Save</button>
                </div>
            </form>
        </section>

    </div>

</x-layouts.app>
