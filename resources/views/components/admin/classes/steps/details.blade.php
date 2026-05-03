<x-step.items step="1" title="Details" description="Name and description">
    <div class="grid gap-4 pt-2">
        <x-input wire:model="name" label="Class name" placeholder="Class 4A" />
    </div>

    <div class="mt-4">
        <x-textarea wire:model="description" label="Description" />
    </div>

    <x-alert title="Class code is generated automatically" color="blue" light class="mt-4">
        A unique class code will be created when you save this class.
    </x-alert>
</x-step.items>
