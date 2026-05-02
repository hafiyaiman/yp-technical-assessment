<?php

namespace App\Livewire\Admin\Classes;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
                ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
                ->update(['school_class_id' => $class->id]);
        }

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
        $this->classStep = (string) min(4, ((int) $this->classStep) + 1);
    }

    public function previousClassStep(): void
    {
        $this->classStep = (string) max(1, ((int) $this->classStep) - 1);
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

        $this->toast()->success('Class deleted.')->send();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description', 'subjectIds', 'studentIds']);
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

    public function students()
    {
        return User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'student'))->with('schoolClass')->orderBy('name')->get();
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
