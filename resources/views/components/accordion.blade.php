@props([
    'multiple' => false,
    'flat' => false,
    'chevron' => 'right',
])

@php
    throw_unless(in_array($chevron, ['left', 'right'], true), InvalidArgumentException::class, 'Accordion chevron must be left or right.');
@endphp

<div
    {{ $attributes->class([
        'w-full bg-white dark:bg-dark-800',
        'overflow-hidden rounded-lg border border-zinc-200 shadow-sm dark:border-dark-600' => ! $flat,
        'divide-y divide-zinc-200 dark:divide-dark-600' => true,
    ]) }}
    x-data="{ active: @js($multiple ? [] : null), multiple: @js((bool) $multiple), chevron: @js($chevron) }">
    {{ $slot }}
</div>
