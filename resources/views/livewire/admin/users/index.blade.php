<?php

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.app')] class extends Component {
    use Interactions;

    #[Url]
    public string $roleFilter = '';

    public bool $modal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'student';
    public string $school_class_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);
    }

    public function create(string $role = 'student'): void
    {
        $this->resetForm();
        $this->role = in_array($role, ['system-admin', 'lecturer', 'student'], true) ? $role : 'student';
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
        $this->password = '';
        $this->modal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-users'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId === null ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['system-admin', 'lecturer', 'student'])],
            'school_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'school_class_id' => $validated['role'] === 'student' && filled($validated['school_class_id']) ? (int) $validated['school_class_id'] : null,
            'email_verified_at' => now(),
        ];

        if (filled($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user = User::query()->updateOrCreate(['id' => $this->editingId], $attributes);
        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->roles()->sync([$role->id]);

        session()->flash('status', __('User saved.'));
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
        session()->flash('status', __('User deleted.'));
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'school_class_id']);
        $this->role = 'student';
        $this->resetValidation();
    }

    public function users()
    {
        return User::query()
            ->with(['roles', 'schoolClass'])
            ->when($this->roleFilter !== '', fn($query) => $query->whereHas('roles', fn($query) => $query->where('slug', $this->roleFilter)))
            ->orderBy('name')
            ->get();
    }

    public function classes()
    {
        return SchoolClass::query()->orderBy('name')->get();
    }

    public function headers(): array
    {
        return [['index' => 'name', 'label' => 'User'], ['index' => 'roles', 'label' => 'Role', 'sortable' => false], ['index' => 'class', 'label' => 'Class', 'sortable' => false], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }
}; ?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Users</h1>
            <p class="mt-1 text-sm text-zinc-500">Manage admin, lecturer, and student accounts.</p>
        </div>
        <x-button text="Create User" icon="plus" wire:click="create('{{ $roleFilter ?: 'student' }}')" />
    </div>

    <x-auth-session-status :status="session('status')" />

    <x-card>
        <div class="mb-5 max-w-xs">
            <x-select.native wire:model.live="roleFilter" label="Filter by role">
                <option value="">All users</option>
                <option value="system-admin">System admins</option>
                <option value="lecturer">Lecturers</option>
                <option value="student">Students</option>
            </x-select.native>
        </div>

        <x-table :headers="$this->headers()" :rows="$this->users()" striped>
            @interact('column_name', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->email }}</p>
                </div>
            @endinteract

            @interact('column_roles', $row)
                <div class="flex flex-wrap gap-1">
                    @foreach ($row->roles as $role)
                        <x-badge :text="$role->name" color="gray" light />
                    @endforeach
                </div>
            @endinteract

            @interact('column_class', $row)
                <span class="text-sm text-zinc-600">{{ $row->schoolClass?->name ?? 'Not assigned' }}</span>
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
                No users found.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit User' : 'Create User' }}" size="lg" center>
        <form wire:submit="save" class="space-y-4">
            <x-input wire:model="name" label="Name" placeholder="Amina Rahman" />
            <x-input wire:model="email" label="Email" placeholder="user@example.com" />
            <x-password wire:model="password" label="{{ $editingId ? 'New password' : 'Password' }}" />

            <x-select.native wire:model.live="role" label="Role">
                <option value="system-admin">System Admin</option>
                <option value="lecturer">Lecturer</option>
                <option value="student">Student</option>
            </x-select.native>

            @if ($role === 'student')
                <x-select.native wire:model="school_class_id" label="Class">
                    <option value="">No class yet</option>
                    @foreach ($this->classes() as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </x-select.native>
            @endif

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <x-button type="button" text="Cancel" color="gray" outline
                    x-on:click="$tsui.close.modal('modal')" />
                <x-button type="submit" text="{{ $editingId ? 'Update User' : 'Create User' }}" icon="check" />
            </div>
        </form>
    </x-modal>
</div>
