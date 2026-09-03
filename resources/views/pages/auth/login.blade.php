<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Pulse</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] antialiased">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div style="width: 100%; max-width: 400px;">

            <div class="text-center mb-8">
                <h1 style="font-size: 1.75rem; font-weight: 700; color: var(--color-text-primary); letter-spacing: -0.02em;">Pulse</h1>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--color-text-muted);">Sign in to continue</p>
            </div>

            <div class="card">
                <div class="card__body">
                    <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 1.25rem;">
                        @csrf

                        <div>
                            <label for="email" style="display: block; font-size: 0.8125rem; font-weight: 500; color: var(--color-text-muted); margin-bottom: 0.375rem;">
                                Email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                autofocus
                                value="{{ old('email') }}"
                                class="form-input @error('email') form-input--error @enderror"
                                style="width: 100%;"
                            >
                            @error('email')
                                <p style="margin-top: 0.375rem; font-size: 0.8125rem; color: #ef4444;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" style="display: block; font-size: 0.8125rem; font-weight: 500; color: var(--color-text-muted); margin-bottom: 0.375rem;">
                                Password
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                class="form-input"
                                style="width: 100%;"
                            >
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                style="width: 1rem; height: 1rem; accent-color: var(--color-brand, #6366f1); cursor: pointer;"
                            >
                            <label for="remember" style="font-size: 0.875rem; color: var(--color-text-muted); cursor: pointer;">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn--primary" style="width: 100%; justify-content: center; padding: 0.625rem 1rem;">
                            Sign in
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
