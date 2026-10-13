@props(['icon' => 'document', 'title' => 'Belum ada data', 'message' => null, 'actionLabel' => null, 'actionUrl' => null, 'compact' => false])

<div {{ $attributes->class(['flex flex-col items-center justify-center text-center', $compact ? 'py-8' : 'py-14']) }}>
    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-7 w-7" />
    </div>
    <h3 class="text-sm font-semibold text-slate-700">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $message }}</p>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-4">{{ $actionLabel }}</a>
    @elseif ($actionLabel)
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
