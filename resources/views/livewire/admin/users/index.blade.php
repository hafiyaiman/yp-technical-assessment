<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Users</h1>
            <p class="mt-1 text-sm text-zinc-500">Manage admin, lecturer, and student accounts.</p>
        </div>
        <x-button text="Create User" icon="plus" wire:click="create" loading="create" />
    </div>

    <x-card>
        <x-admin.users.filters
            :role-options="$this->roleOptions()"
            :class-options="$this->classOptions()"
            :subject-options="$this->subjectOptions()"
        />

        <x-admin.users.table :headers="$this->headers()" :rows="$this->users()" />
    </x-card>

    <x-admin.users.modal
        :modal-mode="$modalMode"
        :editing-id="$editingId"
        :name="$name"
        :email="$email"
        :role="$role"
        :classes="$this->classes()"
        :role-options="$this->roleOptions()"
        :teaching-assignment-keys="$teachingAssignmentKeys"
        :teaching-groups="$this->classSubjectGroups()"
        :selected-teaching-options="$this->selectedTeachingOptions()"
    />
</div>
