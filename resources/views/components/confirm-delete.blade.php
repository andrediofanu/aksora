@props(['label' => 'Hapus', 'title' => 'Hapus data ini?', 'message' => 'Tindakan ini tidak dapat dibatalkan.', 'method' => 'DELETE', 'url' => '', 'modalId' => null])

@php($modalId = $modalId ?? 'confirm-'.md5($label.$title.$attributes->toHtml().$slot))

<button type="button" @click="$dispatch('open-modal', '{{ $modalId }}')"
    {{ $attributes->class(['btn btn-danger']) }} aria-haspopup="dialog">
    @if ($icon ?? null)<x-icon :name="$icon" class="h-4 w-4" />@endif
    {{ $label }}
</button>

<x-modal :id="$modalId" :title="$title">
    <p class="text-sm text-slate-600">{{ $message }}</p>
    <form action="{{ $url }}" method="POST" class="mt-4">
        @csrf
        @method($method)
        <div class="flex justify-end gap-2">
            <button type="button" @click="open = false" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-danger">Ya, hapus</button>
        </div>
    </form>
</x-modal>
