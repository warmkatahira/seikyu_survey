<!DOCTYPE html>
<html lang="ja" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン | 請求書作成 実態調査</title>
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 text-slate-900">
    <div class="w-full max-w-sm">
        <h1 class="text-center text-lg font-semibold">請求書作成　実態調査</h1>
        <p class="mt-1 text-center text-sm text-slate-600">配布されたアカウントでログインしてください。</p>

        <form method="POST" action="{{ route('login') }}"
            class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xs">
            @csrf

            @if ($errors->any())
                <div class="rounded-md border border-rose-300 bg-rose-50 px-3 py-2 text-sm text-rose-900">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($devCredentials)
                <p class="rounded-md border border-sky-300 bg-sky-50 px-3 py-2 text-xs text-sky-900">
                    開発環境のため、管理者アカウント（{{ $devCredentials['login_id'] }}）を自動入力しています。
                </p>
            @endif

            <x-field name="login_id" label="ログインID" required>
                <x-text-input name="login_id" :value="$devCredentials['login_id'] ?? null"
                    autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required />
            </x-field>

            <x-field name="password" label="パスワード" required>
                <x-text-input name="password" type="password" :value="$devCredentials['password'] ?? null"
                    autocomplete="current-password" required />
            </x-field>

            <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                ログイン
            </button>
        </form>
    </div>
</body>
</html>
