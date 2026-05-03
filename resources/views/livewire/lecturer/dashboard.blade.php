<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    @php($stats = $this->stats())

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Your assigned classes, exams, submissions, and
                marking workload.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button text="My Classes" icon="academic-cap" color="gray" outline :href="route('lecturer.teaching.index')" navigate />
            <x-button text="Create Exam" icon="plus" :href="route('lecturer.exams.index')" navigate />
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stats animated :number="$stats['students']" title="Students" icon="users" color="blue"
            href="{{ route('lecturer.teaching.index') }}" navigate footer="{{ $stats['classes'] }} assigned classes">
        </x-stats>

        <x-stats :number="$stats['publishedExams'] . ' / ' . $stats['exams']" title="Published Exams" icon="document-check" color="green"
            href="{{ route('lecturer.exams.index') }}" navigate footer="Total exams published">
        </x-stats>

        <x-stats animated :number="$stats['pendingMarking']" title="Pending Marking" icon="pencil-square" color="yellow"
            href="{{ route('lecturer.exams.index') }}" navigate footer="Submitted attempts need marking">
        </x-stats>

        <x-stats :number="$stats['averageScore'] . '%'" title="Average Score" icon="chart-bar" color="purple"
            href="{{ route('lecturer.exams.index') }}" navigate
            footer="{{ $stats['submissions'] }} completed submissions">
        </x-stats>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.6fr)]">
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Recent Exams</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Latest papers created under your teaching
                        assignments.</p>
                </div>
                <x-button text="View Exams" sm outline :href="route('lecturer.exams.index')" navigate />
            </div>

            <div class="mt-5 overflow-hidden rounded-md border border-zinc-200 dark:border-dark-600">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-dark-600">
                    <thead
                        class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-dark-800 dark:text-dark-300">
                        <tr>
                            <th class="px-4 py-3">Exam</th>
                            <th class="px-4 py-3">Class / Subject</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Submissions</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-dark-700 dark:bg-dark-900">
                        @forelse ($this->recentExams() as $exam)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $exam->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-dark-300">
                                        {{ $exam->duration_minutes }} min / {{ $exam->questions_count }} questions</p>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">
                                    {{ $exam->schoolClass->name }}<br>
                                    <span class="text-xs">{{ $exam->subject->name }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <x-badge :text="str($exam->status->value)->headline()" :color="$exam->status === \App\Enums\ExamStatus::Published
                                        ? 'green'
                                        : ($exam->status === \App\Enums\ExamStatus::Closed
                                            ? 'red'
                                            : 'gray')" light />
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $exam->attempts_count }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-dark-300">
                                        {{ $exam->pending_marking_count }} pending</p>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <x-button text="Open" sm :href="route('lecturer.exams.show', $exam)" navigate />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-dark-300">
                                    No exams yet. Create one from your assigned class-subject pair.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="space-y-4">
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Teaching Load</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">{{ $stats['assignments'] }}
                            class-subject assignments</p>
                    </div>
                    <x-badge :text="$stats['joinRequests'] . ' join requests'" color="blue" light />
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($this->assignments() as $assignment)
                        <a href="{{ route('lecturer.teaching.show', $assignment) }}" wire:navigate
                            class="block rounded-md border border-zinc-200 p-3 transition hover:border-primary-300 hover:bg-primary-50 dark:border-dark-600 dark:hover:border-primary-500 dark:hover:bg-dark-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-zinc-950 dark:text-white">
                                        {{ $assignment->schoolClass->name }}</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                                        {{ $assignment->subject->name }}</p>
                                </div>
                                <x-badge :text="$assignment->exams_count . ' exams'" color="gray" light />
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-dark-300">No teaching assignments yet.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Pending Marking</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Latest submitted attempts.</p>
                    </div>
                    <x-badge :text="$stats['pendingMarking'] . ' pending'" color="yellow" light />
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($this->pendingAttempts() as $attempt)
                        <div class="rounded-md border border-zinc-200 p-3 dark:border-dark-600">
                            <p class="font-medium text-zinc-950 dark:text-white">{{ $attempt->student->name }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">{{ $attempt->exam->title }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span
                                    class="text-xs text-zinc-500 dark:text-dark-300">{{ $attempt->submitted_at?->diffForHumans() ?? 'Submitted' }}</span>
                                <x-button text="Mark" xs :href="route('lecturer.exams.submissions', $attempt->exam)" navigate />
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-dark-300">No submissions need marking right now.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </section>
</div>
