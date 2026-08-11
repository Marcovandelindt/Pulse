@props(['name', 'label' => null, 'rows' => 4, 'placeholder' => null])

<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="form-textarea"
        placeholder="{{ $placeholder }}"
        {{ $attributes }}
    >{{ old($name) }}</textarea>
    <x-form.error :name="$name" />
</div>
