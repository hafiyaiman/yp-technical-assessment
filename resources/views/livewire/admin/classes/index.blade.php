<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Classes</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Group students and assign subjects to each class.</p>
        </div>
        <x-button text="Create Class" icon="plus" wire:click="create" />
    </div>

    <x-card>
        <x-admin.classes.filters :subject-options="$this->subjectOptions()" />
        <x-admin.classes.table :headers="$this->headers()" :rows="$this->classes()" />
    </x-card>

    <x-admin.classes.modal
        :editing-id="$editingId"
        :class-step="$classStep"
        :subjects="$this->subjects()"
        :students="$this->students()"
        :student-ids="$studentIds"
        :subject-ids="$subjectIds"
        :name="$name"
        :code="$code"
    />
</div>
