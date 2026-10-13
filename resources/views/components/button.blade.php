@props([
    'variant' => 'primary',
    'size' => 'md',
    'tag' => 'button',
    'href' => null,
    'type' => null,
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
    ];
    $class = $variants[$variant] ?? $variants['primary'];
    if ($size === 'sm') {
        $class .= ' btn-sm';
    }
    // x-button should not render a default type when used as a plain trigger
    // inside forms unless explicitly requested.
    $type = $type ?? ($tag === 'button' ? 'button' : null);
@endphp

@if ($tag === 'a')
    <a {{ $attributes->merge(['href' => $href, 'class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $class]) }}>{{ $slot }}</button>
@endif
