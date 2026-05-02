@props([
    'attempt',
])

<article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-dark-600 dark:bg-dark-800">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <x-badge :text="$attempt->exam->subject->name" color="gray" light />
            <h2 class="mt-2 truncate text-lg font-semibold text-zinc-950 dark:text-white">{{ $attempt->exam->title }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                Submitted {{ $attempt->submitted_at?->diffForHumans() ?? 'not submitted' }}
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:items-end">
            <div class="flex items-center gap-3">
                <x-badge :text="str($attempt->status->value)->headline()" :color="$attempt->status === \App\Enums\ExamAttemptStatus::Graded ? 'green' : ($attempt->status === \App\Enums\ExamAttemptStatus::Expired ? 'red' : 'yellow')" light />
                @if ($attempt->status === \App\Enums\ExamAttemptStatus::Graded)
                    <span class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $attempt->score }} / {{ $attempt->max_score }} marks</span>
                @endif
            </div>

            <x-button :text="$attempt->status === \App\Enums\ExamAttemptStatus::Graded ? 'View Result' : 'View Status'" :href="$attempt->status === \App\Enums\ExamAttemptStatus::Graded
                ? route('student.results.show', $attempt)
                : route('student.attempts.submitted', $attempt)" navigate />
        </div>
    </div>
</article>
