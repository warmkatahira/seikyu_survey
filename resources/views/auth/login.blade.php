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
{{-- 案C: a soft teal backdrop (blurred glows over faint ledger lines, see .login-backdrop in
     app.css) with a slightly see-through card on top. --}}
<body class="login-backdrop flex min-h-screen items-center justify-center px-4 py-10 text-slate-900">
    <form method="POST" action="{{ route('login') }}"
        class="w-full max-w-sm space-y-4 rounded-[18px] border border-white/90 bg-white/90 px-6 py-7 shadow-[0_20px_40px_rgba(124,45,18,0.12)] backdrop-blur-md">
        @csrf

        <div class="flex items-center gap-3">
            <img src="/favicon.svg" alt="" class="size-11 flex-none">
            <div>
                <h1 class="text-base font-semibold">請求書作成　実態調査</h1>
                <p class="text-xs text-teal-800">配布されたアカウントでログインしてください。</p>
            </div>
        </div>

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
                autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required
                class="rounded-lg! focus:border-teal-500! focus:ring-teal-500! focus:outline-hidden" />
        </x-field>

        <x-field name="password" label="パスワード" required>
            <x-text-input name="password" type="password" :value="$devCredentials['password'] ?? null"
                autocomplete="current-password" required
                class="rounded-lg! focus:border-teal-500! focus:ring-teal-500! focus:outline-hidden" />
        </x-field>

        <button type="submit"
            class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-md shadow-teal-600/25 transition hover:bg-teal-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">
            ログイン
        </button>
    </form>
</body>
</html>
