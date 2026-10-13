@props([
    'name',
    'label' => null,
    'rows' => 4,
    'id' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $id ?? $name;
    $error = $errors->first($name);
    $slotContent = trim($slot);
    $inputAttributes = $attributes->except(['label', 'id'])->merge([
        'id' => $id,
        'name' => $name,
        'rows' => $rows,
        'class' => 'textarea',
    ]);
    if ($required) {
        $inputAttributes = $inputAttributes->merge(['required' => 'required']);
    }
    if ($placeholder) {
        $inputAttributes = $inputAttributes->merge(['placeholder' => $placeholder]);
    }
    if ($error) {
        $inputAttributes = $inputAttributes->merge(['aria-invalid' => 'true']);
    }
@endphp

<div {{ $attributes->only(['class']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <textarea {{ $inputAttributes }}>{{ $slotContent }}</textarea>
    @if ($error)
        <p class="field-error">{{ $error }}</p>
    @endif
</div>
