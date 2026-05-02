<?php

namespace App\Livewire\Lecturer\Teaching;

use App\Enums\ExamAttemptStatus;
use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('view-assigned-classes'), 403);
    }

    public function assignments()
    {
        return TeachingAssignment::query()
            ->with(['schoolClass.students', 'subject'])
            ->withCount([
                'exams',
                'exams as pending_marking_count' => fn ($query) => $query->whereHas('attempts', fn ($query) => $query->where('status', ExamAttemptStatus::Submitted->value)),
            ])
            ->where('lecturer_id', auth()->id())
            ->orderBy(
                SchoolClass::query()
                    ->select('name')
                    ->whereColumn('school_classes.id', 'teaching_assignments.school_class_id')
                    ->limit(1),
            )
            ->get();
    }

    public function render(): View
    {
        return view('livewire.lecturer.teaching.index');
    }
}
