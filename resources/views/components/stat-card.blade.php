@props([
    'label' => '',
    'value' => '0',
    'icon' => 'chart',
    'color' => 'indigo',
    'tone' => null,
    'href' => null,
])

@php
    // `tone` is an alias of `color` for convenience.
    $color = $tone ?? $color;

    $palette = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
    $classes = $palette[$color] ?? $palette['indigo'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card flex items-center gap-4 px-5 py-4 transition hover:shadow']) }}
>
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $classes }}">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <span class="min-w-0">
        <span class="block truncate text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</span>
        <span class="mt-0.5 block text-xl font-bold text-slate-800">{{ $value }}</span>
    </span>
</{{ $tag }}>
