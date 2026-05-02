<x-step.items step="1" title="Details" description="Name and code">
    <div class="grid gap-4 pt-2 sm:grid-cols-2">
        <x-input wire:model="name" label="Class name" placeholder="Class 4A" />
        <x-input wire:model="code" label="Class code" placeholder="CLASS-4A" />
    </div>

    <div class="mt-4">
        <x-textarea wire:model="description" label="Description" />
    </div>
</x-step.items>
