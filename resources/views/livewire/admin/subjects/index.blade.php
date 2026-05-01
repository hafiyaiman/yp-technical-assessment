<?php

use App\Models\Subject;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.app')] class extends Component {
    use Interactions;

    public bool $modal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-subjects'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Subject::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['name'],
                'code' => Str::upper($validated['code']),
                'description' => $validated['description'] ?: null,
            ],
        );

        session()->flash('status', __('Subject saved.'));
        $this->resetForm();
        $this->modal = false;
    }

    public function edit(int $id): void
    {
        $subject = Subject::query()->findOrFail($id);

        $this->editingId = $subject->id;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->description = (string) $subject->description;
        $this->modal = true;
    }

    public function askDelete(int $id): void
    {
        $subject = Subject::query()->findOrFail($id);

        $this->dialog()
            ->question('Delete subject?', "This will remove {$subject->name} from class and exam setup.")
            ->confirm('Yes, delete', 'confirmDelete', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        Subject::query()->findOrFail($id)->delete();
        session()->flash('status', __('Subject deleted.'));
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description']);
        $this->resetValidation();
    }

    public function subjects()
    {
        return Subject::query()
            ->withCount(['classes', 'exams'])
            ->orderBy('name')
            ->get();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'Subject'], ['index' => 'classes_count', 'label' => 'Classes'], ['index' => 'exams_count', 'label' => 'Exams'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }
}; ?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Subjects</h1>
            <p class="mt-1 text-sm text-zinc-500">Create reusable subjects that can be attached to classes.</p>
        </div>
        <x-button text="Create Subject" icon="plus" wire:click="create" />
    </div>

    <x-auth-session-status :status="session('status')" />

    <x-card>
        <x-table :headers="$this->headers()" :rows="$this->subjects()" striped>
            @interact('column_name', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->code }}</p>
                </div>
            @endinteract

            @interact('column_action', $row)
                <div class="flex justify-center">
                    <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                        <x-dropdown.items text="Edit" icon="pencil-square" wire:click="edit({{ $row->id }})" />
                        <x-dropdown.items text="Delete" icon="trash" separator
                            wire:click="askDelete({{ $row->id }})" />
                    </x-dropdown>
                </div>
            @endinteract

            <x-slot:empty>
                No subjects yet.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit Subject' : 'Create Subject' }}" size="lg" center>
        <div class="space-y-1">
            <p class="text-sm text-zinc-500">Subjects are reusable and can be attached to one or more classes.</p>
        </div>

        <div class="mt-5">
            <form wire:submit="save" class="space-y-4">
                <x-input wire:model="name" label="Subject name" placeholder="Mathematics" />
                <x-input wire:model="code" label="Subject code" placeholder="MATH" />
                <x-textarea wire:model="description" label="Description" />

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <x-button type="button" text="Cancel" color="gray" outline
                        x-on:click="$tsui.close.modal('modal')" />
                    <x-button type="submit" text="{{ $editingId ? 'Update Subject' : 'Create Subject' }}"
                        icon="check" />
                </div>
            </form>
        </div>
    </x-modal>
</div>
