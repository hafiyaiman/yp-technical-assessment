<?php

use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.app')] class extends Component {
    use Interactions;

    public bool $modal = false;
    public ?int $editingId = null;
    public string $lecturer_id = '';
    public string $school_class_id = '';
    public string $subject_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-teaching-assignments'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modal = true;
    }

    public function edit(int $id): void
    {
        $assignment = TeachingAssignment::query()->findOrFail($id);

        $this->editingId = $assignment->id;
        $this->lecturer_id = (string) $assignment->lecturer_id;
        $this->school_class_id = (string) $assignment->school_class_id;
        $this->subject_id = (string) $assignment->subject_id;
        $this->modal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'lecturer_id' => ['required', 'integer', 'exists:users,id'],
            'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $lecturer = User::query()->with('roles')->findOrFail((int) $validated['lecturer_id']);
        $class = SchoolClass::query()->with('subjects')->findOrFail((int) $validated['school_class_id']);

        if (!$lecturer->hasRole('lecturer')) {
            throw ValidationException::withMessages(['lecturer_id' => __('Select a lecturer user.')]);
        }

        if (!$class->subjects->contains('id', (int) $validated['subject_id'])) {
            throw ValidationException::withMessages(['subject_id' => __('The selected subject must be assigned to this class first.')]);
        }

        $duplicate = TeachingAssignment::query()
            ->where('lecturer_id', $validated['lecturer_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->when($this->editingId !== null, fn($query) => $query->whereKeyNot($this->editingId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['subject_id' => __('This lecturer is already assigned to that class and subject.')]);
        }

        TeachingAssignment::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'lecturer_id' => (int) $validated['lecturer_id'],
                'school_class_id' => (int) $validated['school_class_id'],
                'subject_id' => (int) $validated['subject_id'],
            ],
        );

        session()->flash('status', __('Teaching assignment saved.'));
        $this->resetForm();
        $this->modal = false;
    }

    public function askDelete(int $id): void
    {
        $assignment = TeachingAssignment::query()
            ->with(['lecturer', 'schoolClass', 'subject'])
            ->withCount('exams')
            ->findOrFail($id);

        if ($assignment->exams_count > 0) {
            $this->dialog()->warning('Assignment has exams', 'Close or reassign its exams before deleting this teaching assignment.')->send();

            return;
        }

        $this->dialog()
            ->question('Delete assignment?', "{$assignment->lecturer->name} will no longer teach {$assignment->subject->name} for {$assignment->schoolClass->name}.")
            ->confirm('Yes, delete', 'confirmDelete', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        $assignment = TeachingAssignment::query()->withCount('exams')->findOrFail($id);
        abort_if($assignment->exams_count > 0, 422);

        $assignment->delete();
        session()->flash('status', __('Teaching assignment deleted.'));
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'lecturer_id', 'school_class_id', 'subject_id']);
        $this->resetValidation();
    }

    public function assignments()
    {
        return TeachingAssignment::query()
            ->with(['lecturer', 'schoolClass', 'subject'])
            ->withCount('exams')
            ->latest()
            ->get();
    }

    public function lecturers()
    {
        return User::query()->whereHas('roles', fn($query) => $query->where('slug', 'lecturer'))->orderBy('name')->get();
    }

    public function classes()
    {
        return SchoolClass::query()->with('subjects')->orderBy('name')->get();
    }

    public function subjects()
    {
        return filled($this->school_class_id) ? SchoolClass::query()->with('subjects')->find($this->school_class_id)?->subjects()->orderBy('name')->get() ?? collect() : collect();
    }

    public function headers(): array
    {
        return [['index' => 'lecturer', 'label' => 'Lecturer', 'sortable' => false], ['index' => 'class_subject', 'label' => 'Class / Subject', 'sortable' => false], ['index' => 'exams_count', 'label' => 'Exams'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }
}; ?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Teaching Assignments</h1>
            <p class="mt-1 text-sm text-zinc-500">Assign lecturers to the class-subject pairs they teach.</p>
        </div>
        <x-button text="Create Assignment" icon="plus" wire:click="create" />
    </div>

    <x-auth-session-status :status="session('status')" />

    <x-card>
        <x-table :headers="$this->headers()" :rows="$this->assignments()" striped>
            @interact('column_lecturer', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->lecturer->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->lecturer->email }}</p>
                </div>
            @endinteract

            @interact('column_class_subject', $row)
                <div>
                    <p class="text-zinc-800">{{ $row->schoolClass->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->subject->name }}</p>
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
                No teaching assignments yet.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit Assignment' : 'Create Assignment' }}" size="lg" center>
        <form wire:submit="save" class="space-y-4">
            <x-select.native wire:model="lecturer_id" label="Lecturer">
                <option value="">Select lecturer</option>
                @foreach ($this->lecturers() as $lecturer)
                    <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
                @endforeach
            </x-select.native>

            <x-select.native wire:model.live="school_class_id" label="Class">
                <option value="">Select class</option>
                @foreach ($this->classes() as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </x-select.native>

            <x-select.native wire:model="subject_id" label="Subject">
                <option value="">Select subject</option>
                @foreach ($this->subjects() as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </x-select.native>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <x-button type="button" text="Cancel" color="gray" outline
                    x-on:click="$tsui.close.modal('modal')" />
                <x-button type="submit" text="{{ $editingId ? 'Update Assignment' : 'Create Assignment' }}"
                    icon="check" />
            </div>
        </form>
    </x-modal>
</div>
