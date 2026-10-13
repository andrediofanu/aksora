@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $id ?? $name;
    $error = $errors->first($name);
    $inputAttributes = $attributes->except(['label', 'id'])->merge([
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'class' => 'input',
    ]);
    if ($required) {
        $inputAttributes = $inputAttributes->merge(['required' => 'required']);
    }
    if ($placeholder) {
        $inputAttributes = $inputAttributes->merge(['placeholder' => $placeholder]);
    }
    if (! $attributes->has('value') && $value !== null) {
        $inputAttributes = $inputAttributes->merge(['value' => $value]);
    }
    if ($error) {
        $inputAttributes = $inputAttributes->merge(['aria-invalid' => 'true']);
    }
@endphp

<div {{ $attributes->only(['class']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <input {{ $inputAttributes }} />
    @if ($error)
        <p class="field-error">{{ $error }}</p>
    @endif
</div>
