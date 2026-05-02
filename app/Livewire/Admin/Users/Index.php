<?php

namespace App\Livewire\Admin\Users;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
    public string $modalMode = 'form';
    public ?int $editingId = null;
    public ?int $assigningClassUserId = null;
    public ?int $assigningTeachingUserId = null;
    public string $search = '';
    public $roleFilters = [];
    public $classFilters = [];
    public $subjectFilters = [];
    public string $name = '';
    public string $email = '';
    public string $role = 'student';
    public string $school_class_id = '';
    public string $teachingSearch = '';
    public array $teachingAssignmentKeys = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);
    }

    public function create(string $role = 'student'): void
    {
        $this->resetForm();
        $this->role = in_array($role, ['system-admin', 'lecturer', 'student'], true) ? $role : 'student';
        $this->modalMode = 'form';
        $this->modal = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()
            ->with(['roles', 'schoolClass'])
            ->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->slug ?? 'student';
        $this->school_class_id = (string) $user->school_class_id;
        $this->modalMode = 'form';
        $this->modal = true;
    }

    public function assignClass(int $id): void
    {
        $user = User::query()
            ->with(['roles', 'schoolClass'])
            ->findOrFail($id);

        abort_unless($user->hasRole('student'), 403);

        $this->resetValidation();
        $this->assigningClassUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->school_class_id = (string) $user->school_class_id;
        $this->modalMode = 'class';
        $this->modal = true;
    }

    public function manageTeaching(int $id): void
    {
        $user = User::query()
            ->with(['roles', 'teachingAssignments.schoolClass', 'teachingAssignments.subject'])
            ->findOrFail($id);

        abort_unless($user->hasRole('lecturer'), 403);

        $this->resetValidation();
        $this->assigningTeachingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->teachingAssignmentKeys = $user->teachingAssignments->map(fn (TeachingAssignment $assignment) => $assignment->school_class_id . ':' . $assignment->subject_id)->values()->all();
        $this->teachingSearch = '';
        $this->modalMode = 'teaching';
        $this->modal = true;
    }

    public function selectVisibleTeachingAssignments(): void
    {
        $visibleKeys = $this->classSubjectGroups()
            ->flatMap(fn (array $group) => collect($group['subjects'])->pluck('value'));

        $this->teachingAssignmentKeys = collect($this->teachingAssignmentKeys)
            ->merge($visibleKeys)
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();
    }

    public function clearTeachingAssignments(): void
    {
        $this->teachingAssignmentKeys = [];
    }

    public function saveClassAssignment(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);

        $validated = $this->validate([
            'school_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $user = User::query()->findOrFail($this->assigningClassUserId);
        abort_unless($user->hasRole('student'), 403);

        $user->update([
            'school_class_id' => filled($validated['school_class_id']) ? (int) $validated['school_class_id'] : null,
        ]);

        $this->toast()->success('Class assignment saved.')->send();
        $this->resetForm();
        $this->modal = false;
    }

    public function saveTeachingAssignments(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);

        $user = User::query()->findOrFail($this->assigningTeachingUserId);
        abort_unless($user->hasRole('lecturer'), 403);

        $validKeys = $this->classSubjectOptions()->pluck('value')->all();
        $selectedKeys = collect($this->teachingAssignmentKeys)->map(fn ($key) => (string) $key)->unique()->values();

        if ($selectedKeys->diff($validKeys)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'teachingAssignmentKeys' => __('One or more selected class-subject pairs are invalid.'),
            ]);
        }

        DB::transaction(function () use ($user, $selectedKeys): void {
            $selectedPairs = $selectedKeys->map(function (string $key): array {
                [$classId, $subjectId] = explode(':', $key);

                return [(int) $classId, (int) $subjectId];
            });

            $user
                ->teachingAssignments()
                ->get()
                ->each(function (TeachingAssignment $assignment) use ($selectedPairs): void {
                    $keep = $selectedPairs->contains(fn (array $pair) => $pair[0] === $assignment->school_class_id && $pair[1] === $assignment->subject_id);

                    if (! $keep) {
                        $assignment->delete();
                    }
                });

            $selectedPairs->each(function (array $pair) use ($user): void {
                TeachingAssignment::query()->firstOrCreate([
                    'lecturer_id' => $user->id,
                    'school_class_id' => $pair[0],
                    'subject_id' => $pair[1],
                ]);
            });
        });

        $this->toast()->success('Teaching assignments saved.')->send();
        $this->resetForm();
        $this->modal = false;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(['system-admin', 'lecturer', 'student'])],
            'school_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $creating = $this->editingId === null;
        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'school_class_id' => $validated['role'] === 'student' && filled($validated['school_class_id']) ? (int) $validated['school_class_id'] : null,
            'email_verified_at' => now(),
        ];

        if ($creating) {
            $attributes['password'] = Hash::make(Str::random(48));
        }

        $user = User::query()->updateOrCreate(['id' => $this->editingId], $attributes);
        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->roles()->sync([$role->id]);

        if ($creating) {
            $user->notify(new UserInvitation(Password::createToken($user)));
        }

        $this->toast()
            ->success($creating ? 'User invited.' : 'User saved.', $creating ? 'An invitation link has been sent to their email.' : null)
            ->send();
        $this->resetForm();
        $this->modal = false;
    }

    public function askDelete(int $id): void
    {
        $user = User::query()->findOrFail($id);

        if ($user->is(auth()->user())) {
            $this->dialog()->warning('Cannot delete yourself', 'Use another admin account to remove this user.')->send();

            return;
        }

        $this->dialog()
            ->question('Delete user?', "This will permanently remove {$user->name}.")
            ->confirm('Yes, delete', 'confirmDelete', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        $user = User::query()->findOrFail($id);
        abort_if($user->is(auth()->user()), 403);

        $user->delete();
        $this->toast()->success('User deleted.')->send();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'assigningClassUserId', 'assigningTeachingUserId', 'name', 'email', 'school_class_id', 'teachingSearch', 'teachingAssignmentKeys']);
        $this->role = 'student';
        $this->modalMode = 'form';
        $this->resetValidation();
    }

    public function updated($property): void
    {
        if ($property === 'search' || str_starts_with($property, 'roleFilters') || str_starts_with($property, 'classFilters') || str_starts_with($property, 'subjectFilters')) {
            $this->normalizeFilters();
            $this->resetPage();
        }
    }

    public function normalizeFilters(): void
    {
        $this->roleFilters = $this->filterValues('roleFilters');
        $this->classFilters = $this->filterValues('classFilters');
        $this->subjectFilters = $this->filterValues('subjectFilters');
    }

    private function filterValues(string $property): array
    {
        $value = property_exists($this, $property) ? $this->{$property} : [];

        return collect(is_array($value) ? $value : [])
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();
    }

    public function users()
    {
        $roleFilters = $this->filterValues('roleFilters');
        $classFilters = $this->filterValues('classFilters');
        $subjectFilters = $this->filterValues('subjectFilters');

        return User::query()
            ->with(['roles', 'schoolClass', 'teachingAssignments.schoolClass', 'teachingAssignments.subject'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($roleFilters !== [], fn ($query) => $query->whereHas('roles', fn ($query) => $query->whereIn('slug', $roleFilters)))
            ->when($classFilters !== [], function ($query) use ($classFilters): void {
                $classIds = array_map('intval', $classFilters);

                $query->where(function ($query) use ($classIds): void {
                    $query->whereIn('school_class_id', $classIds)->orWhereHas('teachingAssignments', fn ($query) => $query->whereIn('school_class_id', $classIds));
                });
            })
            ->when($subjectFilters !== [], function ($query) use ($subjectFilters): void {
                $subjectIds = array_map('intval', $subjectFilters);

                $query->whereHas('teachingAssignments', fn ($query) => $query->whereIn('subject_id', $subjectIds));
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function classes()
    {
        return SchoolClass::query()->orderBy('name')->get();
    }

    public function subjects()
    {
        return Subject::query()->orderBy('name')->get();
    }

    public function roleOptions()
    {
        return Role::query()->orderBy('name')->get()->map(fn (Role $role) => ['label' => $role->name, 'value' => $role->slug])->all();
    }

    public function classOptions()
    {
        return $this->classes()->map(fn (SchoolClass $class) => ['label' => "{$class->name} ({$class->code})", 'value' => $class->id])->all();
    }

    public function subjectOptions()
    {
        return $this->subjects()->map(fn (Subject $subject) => ['label' => "{$subject->name} ({$subject->code})", 'value' => $subject->id])->all();
    }

    public function classSubjectOptions()
    {
        return SchoolClass::query()
            ->with('subjects')
            ->orderBy('name')
            ->get()
            ->flatMap(
                fn (SchoolClass $class) => $class->subjects->sortBy('name')->map(
                    fn (Subject $subject) => [
                        'label' => "{$class->name} / {$subject->name}",
                        'value' => "{$class->id}:{$subject->id}",
                        'description' => "{$class->code} - {$subject->code}",
                    ],
                ),
            )
            ->values();
    }

    public function classSubjectGroups()
    {
        $term = str($this->teachingSearch)->lower()->trim()->toString();

        return SchoolClass::query()
            ->with('subjects')
            ->orderBy('name')
            ->get()
            ->map(function (SchoolClass $class) use ($term): ?array {
                $subjects = $class->subjects->sortBy('name')->values();

                if ($term !== '') {
                    $classMatches = str_contains(strtolower("{$class->name} {$class->code}"), $term);

                    if (! $classMatches) {
                        $subjects = $subjects
                            ->filter(fn (Subject $subject) => str_contains(strtolower("{$subject->name} {$subject->code}"), $term))
                            ->values();
                    }
                }

                if ($subjects->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->code,
                    'subjects' => $subjects
                        ->map(fn (Subject $subject) => [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'code' => $subject->code,
                            'value' => "{$class->id}:{$subject->id}",
                        ])
                        ->all(),
                ];
            })
            ->filter()
            ->values();
    }

    public function selectedTeachingOptions()
    {
        $selected = collect($this->teachingAssignmentKeys)->map(fn ($key) => (string) $key)->all();

        return $this->classSubjectOptions()
            ->filter(fn (array $option) => in_array((string) $option['value'], $selected, true))
            ->values();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'User'], ['index' => 'roles', 'label' => 'Role', 'sortable' => false], ['index' => 'class', 'label' => 'Class / Teaching', 'sortable' => false], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }

    public function render(): View
    {
        return view('livewire.admin.users.index');
    }
}
