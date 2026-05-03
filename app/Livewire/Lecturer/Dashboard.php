<?php

namespace App\Livewire\Lecturer;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Models\ClassJoinRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('lecturer'), 403);
    }

    public function stats(): array
    {
        $assignmentIds = $this->assignmentIds();
        $classIds = $this->classIds();
        $examIds = $this->examIds();

        $gradedAttempts = ExamAttempt::query()
            ->whereIn('exam_id', $examIds)
            ->where('status', ExamAttemptStatus::Graded)
            ->where('max_score', '>', 0);

        $averageScore = (clone $gradedAttempts)->count() > 0
            ? round((clone $gradedAttempts)->avg(DB::raw('(score * 100.0) / max_score')), 1)
            : 0;

        return [
            'assignments' => count($assignmentIds),
            'classes' => count($classIds),
            'students' => User::query()
                ->whereIn('school_class_id', $classIds)
                ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
                ->count(),
            'exams' => count($examIds),
            'publishedExams' => Exam::query()->whereIn('id', $examIds)->where('status', ExamStatus::Published)->count(),
            'pendingMarking' => ExamAttempt::query()->whereIn('exam_id', $examIds)->where('status', ExamAttemptStatus::Submitted)->count(),
            'submissions' => ExamAttempt::query()
                ->whereIn('exam_id', $examIds)
                ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::Expired, ExamAttemptStatus::Graded])
                ->count(),
            'averageScore' => $averageScore,
            'joinRequests' => ClassJoinRequest::query()
                ->whereIn('school_class_id', $classIds)
                ->where('status', \App\Enums\ClassJoinRequestStatus::Pending)
                ->count(),
        ];
    }

    public function recentExams()
    {
        return Exam::query()
            ->with(['schoolClass', 'subject'])
            ->withCount([
                'questions',
                'attempts',
                'attempts as pending_marking_count' => fn ($query) => $query->where('status', ExamAttemptStatus::Submitted),
            ])
            ->assignedTo(auth()->user())
            ->latest()
            ->limit(6)
            ->get();
    }

    public function pendingAttempts()
    {
        return ExamAttempt::query()
            ->with(['student', 'exam.schoolClass', 'exam.subject'])
            ->whereIn('exam_id', $this->examIds())
            ->where('status', ExamAttemptStatus::Submitted)
            ->latest('submitted_at')
            ->limit(6)
            ->get();
    }

    public function assignments()
    {
        return TeachingAssignment::query()
            ->with(['schoolClass.students.roles', 'subject'])
            ->withCount('exams')
            ->where('lecturer_id', auth()->id())
            ->latest()
            ->limit(6)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.lecturer.dashboard');
    }

    private function assignmentIds(): array
    {
        return TeachingAssignment::query()
            ->where('lecturer_id', auth()->id())
            ->pluck('id')
            ->all();
    }

    private function classIds(): array
    {
        return TeachingAssignment::query()
            ->where('lecturer_id', auth()->id())
            ->pluck('school_class_id')
            ->unique()
            ->values()
            ->all();
    }

    private function examIds(): array
    {
        return Exam::query()
            ->assignedTo(auth()->user())
            ->pluck('id')
            ->all();
    }
}
