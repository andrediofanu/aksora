@props([
    'title' => null,
    'subtitle' => null,
    'description' => null,
])

@php($subtitle = $subtitle ?? $description)

{{-- Tombol dapat ditulis lewat slot bernama "actions" atau slot bawaan. --}}
@php($actions = isset($actions) ? $actions : $slot)

<div {{ $attributes->class(['mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-xl font-bold text-slate-800 md:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $actions) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
