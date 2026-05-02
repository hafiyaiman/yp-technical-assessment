<?php

namespace App\Livewire\Admin\TeachingAssignments;

use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('layouts.app')]
class Index extends Component
{
    use Interactions;
    use WithPagination;

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

        if (! $lecturer->hasRole('lecturer')) {
            throw ValidationException::withMessages(['lecturer_id' => __('Select a lecturer user.')]);
        }

        if (! $class->subjects->contains('id', (int) $validated['subject_id'])) {
            throw ValidationException::withMessages(['subject_id' => __('The selected subject must be assigned to this class first.')]);
        }

        $duplicate = TeachingAssignment::query()
            ->where('lecturer_id', $validated['lecturer_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->when($this->editingId !== null, fn ($query) => $query->whereKeyNot($this->editingId))
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

        $this->toast()->success('Teaching assignment saved.')->send();
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
        $this->toast()->success('Teaching assignment deleted.')->send();
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
            ->paginate(10);
    }

    public function lecturers()
    {
        return User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'lecturer'))->orderBy('name')->get();
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

    public function render(): View
    {
        return view('livewire.admin.teaching-assignments.index');
    }
}
