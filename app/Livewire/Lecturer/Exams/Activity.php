<?php

namespace App\Livewire\Lecturer\Exams;

use App\Models\AuditLog;
use App\Models\Exam;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Activity extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $exam->load(['teachingAssignment', 'schoolClass', 'subject']);
        abort_unless(auth()->user()->can('manageSubmissions', $exam), 403);

        $this->exam = $exam;
    }

    public function activityLogs()
    {
        return AuditLog::query()
            ->with('actor')
            ->where('subject_type', $this->exam->getMorphClass())
            ->where('subject_id', $this->exam->id)
            ->latest()
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.lecturer.exams.activity');
    }
}
