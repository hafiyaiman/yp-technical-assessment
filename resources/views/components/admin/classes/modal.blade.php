@props([
    'editingId' => null,
    'classStep' => '1',
    'subjects' => collect(),
    'students' => collect(),
    'studentIds' => [],
    'subjectIds' => [],
    'name' => '',
    'code' => '',
])

<x-modal wire title="{{ $editingId ? 'Edit Class' : 'Create Class' }}" size="4xl" center scrollable>
    <div class="space-y-1">
        <p class="text-sm text-zinc-500 dark:text-dark-300">
            Create a class, attach subjects, and tick the students enrolled in this class.
        </p>
    </div>

    <div class="mt-5">
        <form wire:submit="save" class="space-y-5">
            <x-step wire:model.live="classStep" circles navigate navigate-previous>
                <x-admin.classes.steps.details />

                <x-admin.classes.steps.subjects :subjects="$subjects" />

                <x-admin.classes.steps.students
                    :students="$students"
                    :student-ids="$studentIds"
                    :editing-id="$editingId"
                />

                <x-admin.classes.steps.review
                    :name="$name"
                    :code="$code"
                    :subject-ids="$subjectIds"
                    :student-ids="$studentIds"
                />
            </x-step>

            <div class="flex flex-col-reverse gap-2 border-t border-zinc-100 pt-4 sm:flex-row sm:justify-end">
                <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />

                @if ($classStep !== '1')
                    <x-button type="button" text="Previous" icon="arrow-left" color="gray" outline
                        wire:click="previousClassStep" />
                @endif

                @if ($classStep !== '4')
                    <x-button type="button" text="Next" icon="arrow-right" wire:click="nextClassStep" />
                @else
                    <x-button type="submit" text="{{ $editingId ? 'Update Class' : 'Create Class' }}" icon="check" />
                @endif
            </div>
        </form>
    </div>
</x-modal>
