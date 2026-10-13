@props([
    'color' => 'slate',
    'label' => null,
])

@php
    $colors = [
        'slate' => 'badge-slate',
        'green' => 'badge-green',
        'amber' => 'badge-amber',
        'red' => 'badge-red',
        'indigo' => 'badge-indigo',
    ];
    $class = $colors[$color] ?? $colors['slate'];
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    @if (! empty($icon))
        <x-icon :name="$icon" class="h-3.5 w-3.5" />
    @endif
    {{ $label ?? $slot }}
</span>
