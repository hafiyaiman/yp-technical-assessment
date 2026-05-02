<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">My Classes</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Open a class to view students, handled exams,
                submissions, and marking work.</p>
        </div>
        <x-button text="View Exams" icon="clipboard-document-list" outline :href="route('lecturer.exams.index')" navigate />
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->assignments() as $assignment)
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-dark-300">{{ $assignment->subject->name }} ·
                            {{ $assignment->subject->code }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">
                            {{ $assignment->schoolClass->name }}</h2>
                    </div>
                    <x-badge text="{{ $assignment->exams_count }} exams" color="gray" light />
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-md border border-zinc-200 p-3">
                        <p class="text-zinc-500 dark:text-dark-300">Students</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">
                            {{ $assignment->schoolClass->students->count() }}</p>
                    </div>
                    <div class="rounded-md border border-zinc-200 p-3">
                        <p class="text-zinc-500 dark:text-dark-300">Exams</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">
                            {{ $assignment->exams_count }}</p>
                    </div>
                    <div class="rounded-md border border-zinc-200 p-3">
                        <p class="text-zinc-500 dark:text-dark-300">Marking</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">
                            {{ $assignment->pending_marking_count }}</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <x-button text="View Class" icon="eye" :href="route('lecturer.teaching.show', $assignment)" navigate class="flex-1" />
                    {{-- <x-button text="Create Exam" icon="plus" :href="route('lecturer.teaching.exams.create', $assignment)" navigate class="flex-1" /> --}}
                </div>
            </x-card>
        @empty
            <x-card class="md:col-span-2 xl:col-span-3">
                <div class="py-10 text-center">
                    <p class="font-semibold text-zinc-950 dark:text-white">No teaching assignments yet</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Ask a system admin to assign your class and
                        subject before creating exams.</p>
                </div>
            </x-card>
        @endforelse
    </div>
</div>
