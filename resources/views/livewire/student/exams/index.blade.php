<?php

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('take-exams'), 403);
    }

    public function exams()
    {
        $student = auth()->user();

        if ($student->school_class_id === null) {
            return collect();
        }

        return Exam::query()
            ->visibleToStudent($student)
            ->with([
                'subject',
                'schoolClass',
                'questions',
                'attempts' => fn ($query) => $query->where('student_id', $student->id)->with('answers'),
            ])
            ->latest('published_at')
            ->get();
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-zinc-950">Exam</h1>
        <p class="mt-1 text-sm text-zinc-500">Available tests assigned to your class.</p>
    </div>

    @if (auth()->user()->school_class_id === null)
        <x-card>
            <div class="py-10 text-center">
                <p class="text-lg font-semibold text-zinc-950">No class assigned yet</p>
                <p class="mt-2 text-sm text-zinc-500">Ask your lecturer to assign you to a class before taking exams.</p>
            </div>
        </x-card>
    @else
        <div class="space-y-3">
            @forelse ($this->exams() as $exam)
                @php($attempt = $exam->attempts->first())
                @php($answered = $attempt?->answers->count() ?? 0)
                @php($total = $exam->questions->count())

                <x-card>
                    <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_190px_130px_auto] md:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-zinc-950">{{ $exam->title }}</h2>
                                @if (! $attempt)
                                    <x-badge text="New Questions" color="green" light />
                                @endif
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500">{{ $exam->instructions ?: 'Read the instructions before starting.' }}</p>
                            <p class="mt-1 text-xs text-zinc-400">{{ $exam->subject->name }} - {{ $exam->duration_minutes }} minutes</p>
                        </div>

                        <div>
                            <p class="text-xs text-zinc-500">Question paper attended</p>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-2 w-16 rounded-full bg-zinc-200">
                                    <div class="h-2 rounded-full bg-green-500" style="width: {{ $total > 0 ? ($answered / $total) * 100 : 0 }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-zinc-950">{{ $answered }} of {{ $total }}</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-zinc-500">Last Updated</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-950">{{ $exam->updated_at->diffForHumans() }}</p>
                        </div>

                        <div class="md:text-right">
                            @if ($attempt?->status === ExamAttemptStatus::InProgress)
                                <x-button text="Continue" :href="route('student.attempts.show', $attempt)" navigate />
                            @elseif ($attempt?->status === ExamAttemptStatus::Submitted || $attempt?->status === ExamAttemptStatus::Expired)
                                <x-button text="Status" outline :href="route('student.attempts.submitted', $attempt)" navigate />
                            @elseif ($attempt?->status === ExamAttemptStatus::Graded)
                                <x-button text="Result" :href="route('student.results.show', $attempt)" navigate />
                            @else
                                <x-button text="Start" :href="route('student.exams.show', $exam)" navigate />
                            @endif
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div class="py-10 text-center">
                        <p class="text-lg font-semibold text-zinc-950">No exams available</p>
                        <p class="mt-2 text-sm text-zinc-500">Published exams for your class will appear here.</p>
                    </div>
                </x-card>
            @endforelse
        </div>
    @endif
</div>
