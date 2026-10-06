@props(['name', 'label', 'hint' => null, 'note' => null, 'required' => false, 'multiple' => false, 'accent' => false])

@php
    // Trial of 案D: the hint as plain text and the note (an exception or easy-to-miss case) in a
    // ※ box of its own. Set to 'box' to go back to 案C, where both sit in one tinted box.
    $hintStyle = 'note';
    $sentences = fn (?string $text): array => $text === null ? [] : preg_split('/(?<=。)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
@endphp

{{-- data-required-field / data-field-label feed the answer form's 「必須項目 あと◯件」 counter.
     data-field-error makes a question sent back with an error shake (app.css).
     `accent` puts the accent-coloured bar to the left of the label, marking each question on the answer form. --}}
<div {{ $attributes->merge(['class' => 'space-y-1']) }} @if ($required) data-required-field data-field-label="{{ $label }}" @endif
    @if ($errors->has($name) || $errors->has($name.'.*')) data-field-error @endif>
    <label for="{{ $name }}" @class(['flex items-center gap-2 text-sm font-medium text-slate-800', 'border-l-4 border-orange-500 pl-2' => $accent])>
        {{ $label }}
        @if ($required)
            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] leading-none font-medium text-rose-600 ring-1 ring-rose-200 ring-inset">必須</span>
        @endif
        @if ($multiple)
            <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-0.5 text-[11px] leading-none font-medium text-orange-700 ring-1 ring-orange-200 ring-inset">複数選択可</span>
        @endif
    </label>

    @if ($hintStyle === 'note')
        @if ($hint)
            <div class="space-y-0.5 text-[13px] leading-relaxed text-slate-600">
                @foreach ($sentences($hint) as $sentence)
                    <p>{{ $sentence }}</p>
                @endforeach
            </div>
        @endif
        @if ($note)
            <div class="flex gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs leading-relaxed text-amber-800">
                <span class="flex-none font-bold" aria-hidden="true">※</span>
                <div class="min-w-0 space-y-0.5">
                    @foreach ($sentences($note) as $sentence)
                        <p>{{ $sentence }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    @elseif ($hint || $note)
        {{-- 案C: hint and note together in one tinted box, one sentence per line, so it reads apart from the input. --}}
        <div class="flex gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-[13px] leading-relaxed text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="mt-0.5 size-4 flex-none text-orange-600">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
            </svg>
            <div class="min-w-0 space-y-1">
                @foreach ([...$sentences($hint), ...$sentences($note)] as $sentence)
                    <p>{{ $sentence }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{ $slot }}

    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
