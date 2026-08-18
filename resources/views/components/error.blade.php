@props(['messages' => null])

@if ($messages)
    <p {{ $attributes->class('mt-1.5 text-sm text-danger') }}>
        {{ is_array($messages) ? $messages[0] : $messages }}
    </p>
@endif
