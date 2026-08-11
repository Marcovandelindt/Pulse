@if(session('success') || session('error'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        x-init="setTimeout(() => show = false, 4000)"
        class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg border text-sm font-medium
               {{ session('success') ? 'bg-green-900/50 border-green-700 text-green-300' : 'bg-red-900/50 border-red-700 text-red-300' }}"
        style="display: none;"
    >
        {{ session('success') ?? session('error') }}
    </div>
@endif
