@props(['title' => null, 'heading' => null, 'subheading' => null])

<!DOCTYPE html>
<html lang="ja" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | 請求書作成 実態調査' : '請求書作成 実態調査' }}</title>
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('responses.index') }}" class="text-base font-semibold text-slate-900">
                請求書作成 実態調査
            </a>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                <x-nav-link :href="route('responses.index')" :active="request()->routeIs('responses.index')">回答一覧</x-nav-link>
                <x-nav-link :href="route('responses.create')" :active="request()->routeIs('responses.create')">新規回答</x-nav-link>

                @if (auth()->user()?->isAdmin())
                    <span class="hidden h-4 w-px bg-slate-300 sm:block"></span>
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">集計</x-nav-link>
                    <x-nav-link :href="route('admin.offices.index')" :active="request()->routeIs('admin.offices.*')">営業所</x-nav-link>
                    <x-nav-link :href="route('admin.employees.index')" :active="request()->routeIs('admin.employees.*')">従業員</x-nav-link>
                    <x-nav-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')">顧客</x-nav-link>
                    <x-nav-link :href="route('admin.choices.index')" :active="request()->routeIs('admin.choices.*')">選択肢</x-nav-link>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="ms-auto flex items-center gap-3">
                @csrf
                <span class="text-sm text-slate-500">{{ auth()->user()?->name }}</span>
                <button type="submit" class="text-sm text-slate-500 underline underline-offset-2 hover:text-slate-900">
                    ログアウト
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if ($heading)
            <div class="mb-5">
                <h1 class="text-xl font-semibold text-slate-900">{{ $heading }}</h1>
                @if ($subheading)
                    <p class="mt-1 text-sm text-slate-600">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        <x-flash />

        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
