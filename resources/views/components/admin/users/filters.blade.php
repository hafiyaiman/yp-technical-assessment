@props([
    'roleOptions' => [],
    'classOptions' => [],
    'subjectOptions' => [],
])

<div class="mb-5 grid gap-4 xl:grid-cols-4">
    <x-input wire:model.live.debounce.500ms="search" label="Search" icon="magnifying-glass"
        placeholder="Search name or email" />

    <x-select.styled wire:model.live="roleFilters" label="Roles" :options="$roleOptions"
        select="label:label|value:value" searchable multiple />

    <x-select.styled wire:model.live="classFilters" label="Classes" :options="$classOptions"
        select="label:label|value:value" searchable multiple />

    <x-select.styled wire:model.live="subjectFilters" label="Subjects" :options="$subjectOptions"
        select="label:label|value:value" searchable multiple />
</div>
