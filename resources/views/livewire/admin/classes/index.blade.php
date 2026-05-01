<?php

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.app')] class extends Component 
{
    use Interactions;

    public bool $modal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public array $subjectIds = [];
    public array $studentIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-classes'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-classes'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('school_classes', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'subjectIds' => ['array'],
            'subjectIds.*' => ['integer', 'exists:subjects,id'],
            'studentIds' => ['array'],
            'studentIds.*' => ['integer', 'exists:users,id'],
        ]);

        $class = SchoolClass::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['name'],
                'code' => Str::upper($validated['code']),
                'description' => $validated['description'] ?: null,
            ],
        );

        $class->subjects()->sync($this->subjectIds);

        $currentStudents = User::query()->where('school_class_id', $class->id);

        if ($this->studentIds === []) {
            $currentStudents->update(['school_class_id' => null]);
        } else {
            $currentStudents->whereNotIn('id', $this->studentIds)->update(['school_class_id' => null]);
            User::query()
                ->whereIn('id', $this->studentIds)
                ->whereHas('roles', fn($query) => $query->where('slug', 'student'))
                ->update(['school_class_id' => $class->id]);
        }

        session()->flash('status', __('Class saved.'));
        $this->resetForm();
        $this->modal = false;
    }

    public function edit(int $id): void
    {
        $class = SchoolClass::query()
            ->with(['subjects', 'students'])
            ->findOrFail($id);

        $this->editingId = $class->id;
        $this->name = $class->name;
        $this->code = $class->code;
        $this->description = (string) $class->description;
        $this->subjectIds = $class->subjects->pluck('id')->all();
        $this->studentIds = $class->students->pluck('id')->all();
        $this->modal = true;
    }

    public function askDelete(int $id): void
    {
        $class = SchoolClass::query()->findOrFail($id);

        $this->dialog()
            ->question('Delete class?', "This will remove {$class->name} and unassign its linked students.")
            ->confirm('Yes, delete', 'confirmDelete', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        SchoolClass::query()->findOrFail($id)->delete();

        session()->flash('status', __('Class deleted.'));
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description', 'subjectIds', 'studentIds']);
        $this->resetValidation();
    }

    public function classes()
    {
        return SchoolClass::query()
            ->with(['subjects'])
            ->withCount('students')
            ->orderBy('name')
            ->get();
    }

    public function subjects()
    {
        return Subject::query()->orderBy('name')->get();
    }

    public function students()
    {
        return User::query()->whereHas('roles', fn($query) => $query->where('slug', 'student'))->with('schoolClass')->orderBy('name')->get();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'Class'], ['index' => 'subjects', 'label' => 'Subjects', 'sortable' => false], ['index' => 'students_count', 'label' => 'Students'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center', 'align' => 'center']];
    }
}; ?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Classes</h1>
            <p class="mt-1 text-sm text-zinc-500">Group students and assign subjects to each class.</p>
        </div>
        <x-button text="Create Class" icon="plus" wire:click="create" />
    </div>

    <x-auth-session-status :status="session('status')" />

    <x-card>
        <x-table :headers="$this->headers()" :rows="$this->classes()" striped>
            @interact('column_name', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->code }}</p>
                </div>
            @endinteract

            @interact('column_subjects', $row)
                <div class="flex flex-wrap gap-1">
                    @foreach ($row->subjects as $subject)
                        <x-badge :text="$subject->name" color="gray" light />
                    @endforeach
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
                No classes yet.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit Class' : 'Create Class' }}" size="3xl" center scrollable>
        <div class="space-y-1">
            <p class="text-sm text-zinc-500">Create a class, attach subjects, and assign student users in one place.</p>
        </div>

        <div class="mt-5">
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input wire:model="name" label="Class name" placeholder="Class 4A" />
                    <x-input wire:model="code" label="Class code" placeholder="CLASS-4A" />
                </div>

                <x-textarea wire:model="description" label="Description" />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900">Subjects</p>
                        <div class="mt-2 grid max-h-64 gap-2 overflow-y-auto rounded-md border border-zinc-200 p-2">
                            @forelse ($this->subjects() as $subject)
                                <div class="rounded-md border border-zinc-200 px-3 py-2">
                                    <x-checkbox wire:model="subjectIds" :value="$subject->id" :label="$subject->name" />
                                </div>
                            @empty
                                <p class="px-2 py-3 text-sm text-zinc-500">Create subjects before assigning them.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-zinc-900">Students</p>
                        <div class="mt-2 max-h-64 space-y-2 overflow-y-auto rounded-md border border-zinc-200 p-2">
                            @forelse ($this->students() as $student)
                                <div
                                    class="flex items-center justify-between gap-3 rounded-md px-2 py-2 text-sm hover:bg-zinc-50">
                                    <x-checkbox wire:model="studentIds" :value="$student->id" :label="$student->name" />
                                    <span
                                        class="text-xs text-zinc-500">{{ $student->schoolClass?->code ?? 'Unassigned' }}</span>
                                </div>
                            @empty
                                <p class="px-2 py-3 text-sm text-zinc-500">No student users yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-zinc-100 pt-4 sm:flex-row sm:justify-end">
                    <x-button type="button" text="Cancel" color="gray" outline
                        x-on:click="$tsui.close.modal('modal')" />
                    <x-button type="submit" text="{{ $editingId ? 'Update Class' : 'Create Class' }}" icon="check" />
                </div>
            </form>
        </div>
    </x-modal>
</div>
