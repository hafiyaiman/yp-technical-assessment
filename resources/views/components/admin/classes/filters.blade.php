@props([
    'subjectOptions' => [],
])

<div class="mb-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
    <x-input wire:model.live.debounce.500ms="search" label="Search" icon="magnifying-glass"
        placeholder="Search class name or code" />

    <x-select.styled wire:model.live="subjectFilters" label="Subjects" :options="$subjectOptions"
        select="label:label|value:value" searchable multiple />
</div>
