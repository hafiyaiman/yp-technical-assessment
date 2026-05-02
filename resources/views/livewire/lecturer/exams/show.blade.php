<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    @php($summary = $this->summary())

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $exam->title }}</h1>
                <x-badge :text="str($exam->status->value)->headline()" :color="$this->statusColor()" light />
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                {{ $exam->schoolClass->name }} · {{ $exam->subject->name }} · {{ $exam->duration_minutes }} minutes
            </p>
        </div>

        <x-button text="Back to Exams" icon="arrow-left" flat :href="route('lecturer.exams.index')" navigate />
    </div>

    <x-lecturer.exams.tabs :exam="$exam" />

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stats animated :number="$summary['students']" title="Class Students" icon="users" />
        <x-stats animated :number="$summary['questions']" title="Questions" icon="list-bullet" color="secondary" />
        <x-stats animated :number="$summary['attempts']" title="Submissions" icon="clipboard-document-list" color="green" />
        <x-stats animated :number="$summary['pendingMarking']" title="Pending Marking" icon="pencil-square" color="yellow" />
    </section>

    <section>
        <x-card>
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Exam Details</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-dark-300">Class</dt>
                    <dd class="font-medium text-zinc-950 dark:text-white">{{ $exam->schoolClass->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-dark-300">Subject</dt>
                    <dd class="font-medium text-zinc-950 dark:text-white">{{ $exam->subject->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-dark-300">Available From</dt>
                    <dd class="font-medium text-zinc-950 dark:text-white">{{ $exam->available_from?->format('M j, Y g:i A') ?? 'Immediately' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-dark-300">Available Until</dt>
                    <dd class="font-medium text-zinc-950 dark:text-white">{{ $exam->available_until?->format('M j, Y g:i A') ?? 'No end date' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-dark-300">Graded</dt>
                    <dd class="font-medium text-zinc-950 dark:text-white">{{ $summary['graded'] }} / {{ $summary['attempts'] }}</dd>
                </div>
            </dl>
        </x-card>
    </section>
</div>
