<?php

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
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

    public function gradedAttempts()
    {
        return $this->attempts()
            ->filter(fn (ExamAttempt $attempt) => $attempt->status === ExamAttemptStatus::Graded)
            ->values();
    }

    public function pendingAttempts()
    {
        return $this->attempts()
            ->filter(fn (ExamAttempt $attempt) => $attempt->status === ExamAttemptStatus::Submitted)
            ->values();
    }

    public function expiredAttempts()
    {
        return $this->attempts()
            ->filter(fn (ExamAttempt $attempt) => $attempt->status === ExamAttemptStatus::Expired)
            ->values();
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Results</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-dark-300">See graded marks, exams waiting for lecturer review, and expired attempts.</p>
    </div>

    <x-tab selected="graded" scroll-on-mobile>
        <x-tab.items tab="graded" title="Ready ({{ $this->gradedAttempts()->count() }})">
            <div class="space-y-3">
                @forelse ($this->gradedAttempts() as $attempt)
                    <x-student.result-row :attempt="$attempt" />
                @empty
                    <x-student.empty-state title="No marks released yet" description="Graded results will appear here after your lecturer finishes marking." />
                @endforelse
            </div>
        </x-tab.items>

        <x-tab.items tab="pending" title="Waiting Review ({{ $this->pendingAttempts()->count() }})">
            <div class="space-y-3">
                @forelse ($this->pendingAttempts() as $attempt)
                    <x-student.result-row :attempt="$attempt" />
                @empty
                    <x-student.empty-state title="No pending reviews" description="Submitted exams that need marking will appear here." />
                @endforelse
            </div>
        </x-tab.items>

        <x-tab.items tab="expired" title="Expired ({{ $this->expiredAttempts()->count() }})">
            <div class="space-y-3">
                @forelse ($this->expiredAttempts() as $attempt)
                    <x-student.result-row :attempt="$attempt" />
                @empty
                    <x-student.empty-state title="No expired attempts" description="Attempts that passed the server time limit will appear here." />
                @endforelse
            </div>
        </x-tab.items>
    </x-tab>
</div>
