@props([
    'exam',
])

@php
    $attempt = $exam->attempts->first();
    $answered = $attempt?->answers->count() ?? 0;
    $total = $exam->questions->count();
    $progress = $total > 0 ? ($answered / $total) * 100 : 0;
@endphp

<x-card>
    <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_190px_140px_auto] md:items-center">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $exam->title }}</h2>
                @if (! $attempt)
                    <x-badge text="New" color="green" light />
                @elseif ($attempt->status === \App\Enums\ExamAttemptStatus::InProgress)
                    <x-badge text="In Progress" color="blue" light />
                @elseif ($attempt->status === \App\Enums\ExamAttemptStatus::Graded)
                    <x-badge text="Result Ready" color="green" light />
                @elseif ($attempt->status === \App\Enums\ExamAttemptStatus::Expired)
                    <x-badge text="Expired" color="red" light />
                @else
                    <x-badge text="Waiting Review" color="yellow" light />
                @endif
            </div>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500 dark:text-dark-300">
                {{ $exam->instructions ?: 'Read the instructions before starting.' }}
            </p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-dark-300">{{ $exam->subject->name }} / {{ $exam->duration_minutes }} minutes</p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-dark-300">Answered</p>
            <div class="mt-2 flex items-center gap-2">
                <div class="h-2 w-16 rounded-full bg-zinc-200 dark:bg-dark-600">
                    <div class="h-2 rounded-full bg-green-500" style="width: {{ $progress }}%"></div>
                </div>
                <span class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $answered }} of {{ $total }}</span>
            </div>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $attempt?->status === \App\Enums\ExamAttemptStatus::Graded ? 'Marks' : 'Updated' }}</p>
            <p class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">
                @if ($attempt?->status === \App\Enums\ExamAttemptStatus::Graded)
                    {{ $attempt->score }} / {{ $attempt->max_score }}
                @else
                    {{ $exam->updated_at->diffForHumans() }}
                @endif
            </p>
        </div>

        <div class="md:text-right">
            @if ($attempt?->status === \App\Enums\ExamAttemptStatus::InProgress)
                <x-button text="Continue" :href="route('student.attempts.show', $attempt)" navigate />
            @elseif ($attempt?->status === \App\Enums\ExamAttemptStatus::Submitted || $attempt?->status === \App\Enums\ExamAttemptStatus::Expired)
                <x-button text="View Status" outline :href="route('student.attempts.submitted', $attempt)" navigate />
            @elseif ($attempt?->status === \App\Enums\ExamAttemptStatus::Graded)
                <x-button text="View Result" :href="route('student.results.show', $attempt)" navigate />
            @else
                <x-button text="Start" :href="route('student.exams.show', $exam)" navigate />
            @endif
        </div>
    </div>
</x-card>
