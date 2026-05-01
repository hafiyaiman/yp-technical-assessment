<?php

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('view-own-results'), 403);
    }

    public function attempts()
    {
        return ExamAttempt::query()
            ->with(['exam.subject'])
            ->where('student_id', auth()->id())
            ->whereIn('status', [
                ExamAttemptStatus::Submitted,
                ExamAttemptStatus::Expired,
                ExamAttemptStatus::Graded,
            ])
            ->latest()
            ->get();
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-zinc-950">Past results</h1>
        <p class="mt-2 text-sm text-zinc-600">Track submitted exams, pending reviews, and graded results.</p>
    </div>

    <div class="space-y-3">
        @forelse ($this->attempts() as $attempt)
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <x-badge :text="$attempt->exam->subject->name" color="gray" light />
                        <h2 class="mt-2 text-lg font-semibold text-zinc-950">{{ $attempt->exam->title }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">Submitted {{ $attempt->submitted_at?->diffForHumans() ?? 'not submitted' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-badge :text="str($attempt->status->value)->headline()" :color="$attempt->status === ExamAttemptStatus::Graded ? 'green' : ($attempt->status === ExamAttemptStatus::Expired ? 'red' : 'yellow')" light />
                        <x-button text="Details" :href="route('student.results.show', $attempt)" navigate />
                    </div>
                </div>
            </article>
        @empty
            <x-card>
                <div class="py-10 text-center">
                    <p class="text-lg font-semibold text-zinc-950">No past results yet</p>
                    <p class="mt-2 text-sm text-zinc-500">Submitted exams will appear here.</p>
                </div>
            </x-card>
        @endforelse
    </div>
</div>
