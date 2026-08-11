@props(['name', 'label' => null, 'type' => 'text', 'placeholder' => null])

<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        class="form-input"
        placeholder="{{ $placeholder }}"
        {{ $attributes }}
        value="{{ old($name) }}"
    >
    <x-form.error :name="$name" />
</div>
