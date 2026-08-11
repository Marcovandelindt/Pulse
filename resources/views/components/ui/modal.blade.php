@props(['title' => ''])

<div class="modal" x-show="open" x-transition @keydown.escape.window="open = false" style="display:none;">
    <div class="modal__backdrop" @click="open = false"></div>
    <div class="modal__panel">
        <div class="modal__header">
            <h2 class="modal__title">{{ $title }}</h2>
            <button @click="open = false" class="btn btn--icon btn--secondary" type="button">
                &times;
            </button>
        </div>

        {{ $slot }}

        @isset($footer)
            <div class="modal__footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
