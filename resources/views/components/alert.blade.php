@props(['type' => 'info'])

@php
    $styles = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800',
    ];
    $icons = ['info' => 'information-circle', 'success' => 'check-circle', 'warning' => 'exclamation-triangle', 'danger' => 'exclamation-circle'];
@endphp

<div {{ $attributes->class(['rounded-xl border px-4 py-3 text-sm', $styles[$type] ?? $styles['info']]) }} role="alert">
    <div class="flex gap-2">
        <x-icon :name="$icons[$type] ?? 'information-circle'" class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="flex-1">
            @if ($title ?? null)
                <p class="font-semibold">{{ $title }}</p>
            @endif
            <div>{{ $slot }}</div>
        </div>
    </div>
</div>
