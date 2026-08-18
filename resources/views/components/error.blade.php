@props(['messages' => null])

@if ($messages)
    <p {{ $attributes->class('mt-2 text-sm font-semibold text-danger') }}>
        {{ is_array($messages) ? $messages[0] : $messages }}
    </p>
@endif
