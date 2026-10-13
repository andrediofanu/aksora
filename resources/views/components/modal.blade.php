@props(['id', 'title' => 'Konfirmasi', 'maxWidth' => 'md'])

<div x-data="{ open: false }" x-cloak id="{{ $id }}" x-show="open" x-transition.opacity.duration.150ms
     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ $title }}"
     @open-modal.window="if ($event.detail === '{{ $id }}') open = true"
     @close-modal.window="if ($event.detail === '{{ $id }}') open = false">
    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-show="open" x-transition.opacity.duration.150ms
             @click="open = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             @click.outside="open = false" @keydown.escape.window="open = false"
             class="relative w-full {{ $maxWidth === 'lg' ? 'max-w-2xl' : ($maxWidth === 'xl' ? 'max-w-4xl' : 'max-w-md') }} transform rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
                <button type="button" @click="open = false" class="btn btn-ghost -m-1.5 p-1.5" aria-label="Tutup">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
            <div class="px-5 py-4">{{ $slot }}</div>
            @if (isset($footer))
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 rounded-b-2xl">{{ $footer }}</div>
            @endif
        </div>
    </div>
</div>
