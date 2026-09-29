@if (session('status'))
    <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        {{ session('error') }}
    </div>
@endif

@if (session('importErrors'))
    <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900">
        <p class="font-semibold">取り込みを中止しました。下記を修正して再度アップロードしてください。</p>
        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach (session('importErrors') as $importError)
                <li>{{ $importError }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900">
        <ul class="list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
