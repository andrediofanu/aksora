@php
    $success = session('success');
    $error = $errors->any() ? $errors->first() : session('error');
@endphp

@if ($success)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="status"
        class="mb-4 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
    >
        <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
        <span class="flex-1">{{ $success }}</span>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700" aria-label="Tutup">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endif

@if ($error)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 8000)"
        x-show="show"
        x-transition
        role="alert"
        class="mb-4 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
    >
        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
        <span class="flex-1">{{ $error }}</span>
        <button type="button" @click="show = false" class="text-rose-400 hover:text-rose-600" aria-label="Tutup">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endif
