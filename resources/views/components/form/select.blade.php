@props(['name', 'label' => null])

<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" class="form-select" {{ $attributes }}>
        {{ $slot }}
    </select>
    <x-form.error :name="$name" />
</div>
