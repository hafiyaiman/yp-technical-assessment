<?php

namespace App\Livewire\Admin\Subjects;

use App\Models\SchoolClass;
use App\Models\Subject;
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
    public ?int $editingId = null;
    public string $search = '';
    public $classFilters = [];
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

        $this->toast()->success('Subject saved.')->send();
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
        $this->toast()->success('Subject deleted.')->send();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description']);
        $this->resetValidation();
    }

    public function updated($property): void
    {
        if ($property === 'search' || str_starts_with($property, 'classFilters')) {
            $this->classFilters = $this->filterValues('classFilters');
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

    public function subjects()
    {
        $classFilters = $this->filterValues('classFilters');

        return Subject::query()
            ->withCount(['classes', 'exams'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%");
                });
            })
            ->when($classFilters !== [], function ($query) use ($classFilters): void {
                $classIds = array_map('intval', $classFilters);

                $query->whereHas('classes', fn ($query) => $query->whereIn('school_classes.id', $classIds));
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function classes()
    {
        return SchoolClass::query()->orderBy('name')->get();
    }

    public function classOptions()
    {
        return $this->classes()->map(fn (SchoolClass $class) => ['label' => "{$class->name} ({$class->code})", 'value' => $class->id])->all();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'Subject'], ['index' => 'classes_count', 'label' => 'Classes'], ['index' => 'exams_count', 'label' => 'Exams'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }

    public function render(): View
    {
        return view('livewire.admin.subjects.index');
    }
}
