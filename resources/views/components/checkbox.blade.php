@props([
    'name',
    'label' => null,
    'id' => null,
    'checked' => false,
    'value' => '1',
])

@php
    $id = $id ?? $name;
    $error = $errors->first($name);
    $isChecked = old($name, $checked);
    if (is_string($isChecked)) {
        $isChecked = in_array($isChecked, ['1', 'true', 'on', $value], true);
    }
@endphp

<div {{ $attributes->only(['class']) }}>
    <label for="{{ $id }}" class="flex items-start gap-3">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
            @checked($isChecked)
            class="mt-0.5 rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @if ($label)
            <span class="text-sm text-slate-700">{{ $label }}</span>
        @else
            <span class="text-sm text-slate-700">{{ $slot }}</span>
        @endif
    </label>
    @if ($error)
        <p class="field-error ml-6">{{ $error }}</p>
    @endif
</div>
