@props(['title' => 'Pulse'])

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Pulse</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] antialiased">

    <div class="layout">
        <x-layout.sidebar />

        <main class="layout__main">
            <div class="layout__content">
                {{ $slot }}
            </div>
        </main>
    </div>

    <div
        x-data="{ toasts: [] }"
        @toast.window="toasts.push({ message: $event.detail.message, type: $event.detail.type ?? 'success', id: Date.now() })"
        class="toast-container"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="toast"
                :class="toast.type === 'success' ? 'toast--success' : 'toast--error'"
                x-show="true"
                x-transition
                x-init="setTimeout(() => { toasts = toasts.filter(t => t.id !== toast.id) }, 3000)"
            >
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

</body>
</html>
