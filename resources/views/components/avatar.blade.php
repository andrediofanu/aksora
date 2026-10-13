@props(['user' => null, 'name' => null, 'src' => null, 'size' => 'md'])

@php
    $name = $name ?? $user?->name ?? 'Pengguna';
    $src = $src ?? ($user ? \App\Support\Avatar::url($user) : null);
    $sizes = ['sm' => 'h-8 w-8 text-[10px]', 'md' => 'h-10 w-10 text-xs', 'lg' => 'h-14 w-14 text-base', 'xl' => 'h-20 w-20 text-xl'];
    $cls = $sizes[$size] ?? $sizes['md'];
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
@endphp

@if ($src)
    <img {{ $attributes->merge(['class' => "shrink-0 rounded-full object-cover $cls"]) }} src="{{ $src }}" alt="{{ $name }}" loading="lazy" />
@else
    <span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 font-semibold text-white $cls"]) }} aria-hidden="true">{{ $initial }}</span>
@endif
