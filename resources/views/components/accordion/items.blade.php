@props([
    'id',
    'title' => null,
])

<div {{ $attributes->class('bg-white dark:bg-dark-800') }} x-data="{ id: @js($id) }">
    <button
        type="button"
        class="flex w-full items-center gap-3 px-4 py-4 text-left text-sm font-semibold text-zinc-950 transition hover:bg-zinc-50 dark:text-white dark:hover:bg-dark-700"
        x-on:click="
            if (multiple) {
                active = active.includes(id) ? active.filter(item => item !== id) : [...active, id]
            } else {
                active = active === id ? null : id
            }
        ">
        <x-icon name="chevron-down"
            class="h-4 w-4 shrink-0 text-zinc-500 transition dark:text-dark-300"
            x-bind:class="{
                'rotate-180': multiple ? active.includes(id) : active === id,
                'order-first': chevron === 'left',
                'order-last ml-auto': chevron === 'right'
            }" />

        <span class="min-w-0 flex-1 truncate">{{ $title }}</span>
    </button>

    <div
        x-cloak
        x-show="multiple ? active.includes(id) : active === id"
        x-transition.opacity.duration.150ms
        class="border-t border-zinc-200 px-4 py-5 dark:border-dark-600">
        {{ $slot }}
    </div>
</div>
