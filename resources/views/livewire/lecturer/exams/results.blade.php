<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    @php($summary = $this->summary())

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Results</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                {{ $exam->title }} · {{ $exam->schoolClass->name }} · {{ $exam->subject->name }}
            </p>
        </div>

        <x-button text="Back to Exams" icon="arrow-left" flat :href="route('lecturer.exams.index')" navigate />
    </div>

    <x-lecturer.exams.tabs :exam="$exam" />

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stats animated :number="$summary['completion']" title="Completed Attempts" icon="clipboard-document-list" />
        <x-stats animated :number="$summary['average']" title="Average Score" icon="chart-bar" color="secondary" />
        <x-stats animated :number="$summary['highest']" title="Highest Score" icon="arrow-trending-up" color="green" />
        <x-stats animated :number="$summary['pending']" title="Pending Marking" icon="pencil-square" color="yellow" />
    </section>

    <x-card>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Student Results</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Scores are final when attempts are graded.</p>
            </div>
            <x-badge :text="$summary['graded'] . ' graded'" color="green" light />
        </div>

        <div class="mt-5 overflow-hidden rounded-md border border-zinc-200 dark:border-dark-600">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-dark-600">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-dark-800 dark:text-dark-300">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Score</th>
                        <th class="px-4 py-3">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-dark-700 dark:bg-dark-900">
                    @forelse ($this->attempts() as $attempt)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-medium text-zinc-950 dark:text-white">{{ $attempt->student->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $attempt->student->email }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <x-badge :text="str($attempt->status->value)->headline()" :color="$attempt->status === \App\Enums\ExamAttemptStatus::Graded ? 'green' : ($attempt->status === \App\Enums\ExamAttemptStatus::Expired ? 'red' : 'yellow')" light />
                            </td>
                            <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">
                                {{ $attempt->score }} / {{ $attempt->max_score }}
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">
                                {{ $attempt->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-dark-300">
                                No attempts yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
