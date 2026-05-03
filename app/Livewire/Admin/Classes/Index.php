<?php

namespace App\Livewire\Admin\Classes;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
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
    public string $classStep = '1';
    public ?int $editingId = null;
    public string $search = '';
    public $subjectFilters = [];
    public string $subjectSearch = '';
    public string $studentSearch = '';
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
        $this->classStep = '1';
        $this->modal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-classes'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'subjectIds' => ['required', 'array', 'min:1'],
            'subjectIds.*' => ['integer', 'exists:subjects,id'],
            'studentIds' => ['array'],
            'studentIds.*' => ['integer', 'exists:users,id'],
        ], $this->classValidationMessages());

        $savingNewClass = $this->editingId === null;
        $existingClass = $this->editingId === null ? null : SchoolClass::query()->findOrFail($this->editingId);

        $class = SchoolClass::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['name'],
                'code' => $existingClass?->code ?? SchoolClass::generateCode(),
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
                ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
                ->update(['school_class_id' => $class->id]);
        }

        app(AuditLogger::class)->record(
            $savingNewClass ? 'class.created' : 'class.updated',
            ($savingNewClass ? 'Created' : 'Updated').' class '.$class->name.'.',
            $class,
            ['subjects' => $this->subjectIds, 'students' => $this->studentIds],
        );

        $this->toast()->success('Class saved.')->send();
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
        $this->classStep = '1';
        $this->modal = true;
    }

    public function nextClassStep(): void
    {
        $this->validateCurrentStep();

        $this->classStep = (string) min(4, ((int) $this->classStep) + 1);
    }

    public function previousClassStep(): void
    {
        $this->classStep = (string) max(1, ((int) $this->classStep) - 1);
    }

    private function validateCurrentStep(): void
    {
        match ($this->classStep) {
            '1' => $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]),
            '2' => $this->validate([
                'subjectIds' => ['required', 'array', 'min:1'],
                'subjectIds.*' => ['integer', 'exists:subjects,id'],
            ], $this->classValidationMessages()),
            default => null,
        };
    }

    private function classValidationMessages(): array
    {
        return [
            'subjectIds.required' => 'Please select at least one subject.',
            'subjectIds.min' => 'Please select at least one subject.',
        ];
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
        $class = SchoolClass::query()->findOrFail($id);

        app(AuditLogger::class)->record('class.deleted', 'Deleted class '.$class->name.'.', $class);

        $class->delete();

        $this->toast()->success('Class deleted.')->send();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description', 'subjectSearch', 'studentSearch', 'subjectIds', 'studentIds']);
        $this->classStep = '1';
        $this->resetValidation();
    }

    public function updated($property): void
    {
        if ($property === 'search' || str_starts_with($property, 'subjectFilters')) {
            $this->subjectFilters = $this->filterValues('subjectFilters');
            $this->resetPage();
        }
    }

    private function filterValues(string $property): array
    {
        $value = property_exists($this, $property) ? $this->{$property} : [];

        return collect(is_array($value) ? $value : [])
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();
    }

    public function classes()
    {
        $subjectFilters = $this->filterValues('subjectFilters');

        return SchoolClass::query()
            ->with(['subjects'])
            ->withCount('students')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%");
                });
            })
            ->when($subjectFilters !== [], function ($query) use ($subjectFilters): void {
                $subjectIds = array_map('intval', $subjectFilters);

                $query->whereHas('subjects', fn ($query) => $query->whereIn('subjects.id', $subjectIds));
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function subjects()
    {
        return Subject::query()->orderBy('name')->get();
    }

    public function filteredSubjects()
    {
        return Subject::query()
            ->when($this->subjectSearch !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', "%{$this->subjectSearch}%")
                        ->orWhere('code', 'like', "%{$this->subjectSearch}%");
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function students()
    {
        return User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'student'))->with('schoolClass')->orderBy('name')->get();
    }

    public function filteredStudents()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
            ->with('schoolClass')
            ->when($this->studentSearch !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', "%{$this->studentSearch}%")
                        ->orWhere('email', 'like', "%{$this->studentSearch}%");
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function subjectOptions()
    {
        return $this->subjects()->map(fn (Subject $subject) => ['label' => "{$subject->name} ({$subject->code})", 'value' => $subject->id])->all();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'Class'], ['index' => 'subjects', 'label' => 'Subjects', 'sortable' => false], ['index' => 'students_count', 'label' => 'Students'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }

    public function render(): View
    {
        return view('livewire.admin.classes.index');
    }
}
