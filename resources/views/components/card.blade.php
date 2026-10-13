@props([
    'title' => null,
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($header))
        <div class="card-header">
            @if ($title)
                <h2 class="card-title">{{ $title }}</h2>
            @else
                {{ $header }}
            @endif
            @if ($title && isset($actions))
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @elseif(! $title && isset($actions))
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="{{ $flush ? 'card-body-flush' : 'card-body' }}">
        {{ $slot }}
    </div>
</div>
