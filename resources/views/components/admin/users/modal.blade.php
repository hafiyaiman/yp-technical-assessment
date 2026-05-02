@props([
    'modalMode' => 'form',
    'editingId' => null,
    'name' => '',
    'email' => '',
    'role' => 'student',
    'classes' => collect(),
    'roleOptions' => [],
    'teachingAssignmentKeys' => [],
    'teachingGroups' => collect(),
    'selectedTeachingOptions' => collect(),
])

<x-modal wire
    title="{{ $modalMode === 'class' ? 'Assign Class' : ($modalMode === 'teaching' ? 'Manage Teaching Classes' : ($editingId ? 'Edit User' : 'Create User')) }}"
    size="5xl" center scrollable persistent>
    @if ($modalMode === 'class')
        <x-admin.users.modals.assign-class :name="$name" :email="$email" :classes="$classes" />
    @elseif ($modalMode === 'teaching')
        <x-admin.users.modals.manage-teaching
            :name="$name"
            :email="$email"
            :teaching-assignment-keys="$teachingAssignmentKeys"
            :teaching-groups="$teachingGroups"
            :selected-teaching-options="$selectedTeachingOptions"
        />
    @else
        <x-admin.users.modals.user-form
            :editing-id="$editingId"
            :role="$role"
            :classes="$classes"
            :role-options="$roleOptions"
        />
    @endif
</x-modal>
