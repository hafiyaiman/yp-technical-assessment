<?php

namespace App\Livewire\Lecturer\Exams;

use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TeachingAssignment;
use App\Services\AuditLogger;
use App\Services\Exams\ExamPublicationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('layouts.app')]
class Builder extends Component
{
    use Interactions;

    public ?int $examId = null;
    public string $teaching_assignment_id = '';
    public string $title = '';
    public string $instructions = '';
    public string $school_class_id = '';
    public string $subject_id = '';
    public int $duration_minutes = 15;
    public string $available_from = '';
    public string $available_until = '';
    public array $questions = [];

    public function mount(?TeachingAssignment $assignment = null, ?Exam $exam = null): void
    {
        abort_unless(auth()->user()->hasPermission('manage-exams'), 403);

        if ($exam?->exists) {
            $exam->load(['questions.options', 'teachingAssignment.schoolClass', 'teachingAssignment.subject']);
            abort_unless(auth()->user()->can('update', $exam), 403);

            $this->examId = $exam->id;
            $this->teaching_assignment_id = (string) $exam->teaching_assignment_id;
            $this->title = $exam->title;
            $this->instructions = (string) $exam->instructions;
            $this->school_class_id = (string) $exam->school_class_id;
            $this->subject_id = (string) $exam->subject_id;
            $this->duration_minutes = $exam->duration_minutes;
            $this->available_from = $exam->available_from?->format('Y-m-d\TH:i') ?? '';
            $this->available_until = $exam->available_until?->format('Y-m-d\TH:i') ?? '';
            $this->questions = $exam->questions
                ->map(
                    fn (Question $question): array => [
                        'type' => $question->type->value,
                        'prompt' => $question->prompt,
                        'points' => $question->points,
                        'correct_option' => max(0, $question->options->search(fn ($option) => $option->is_correct) ?: 0),
                        'options' => $question->options->map(fn ($option): array => ['text' => $option->text])->values()->all(),
                    ],
                )
                ->values()
                ->all();

            return;
        }

        abort_unless($assignment?->exists, 404);
        $assignment->load(['schoolClass', 'subject']);
        abort_unless($assignment->isOwnedBy(auth()->user()), 403);

        $this->teaching_assignment_id = (string) $assignment->id;
        $this->school_class_id = (string) $assignment->school_class_id;
        $this->subject_id = (string) $assignment->subject_id;
        $this->questions = [$this->blankQuestion(QuestionType::MultipleChoice->value)];
    }

    public function addQuestion(string $type): void
    {
        $this->questions[] = $this->blankQuestion($type);
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addOption(int $questionIndex): void
    {
        $this->questions[$questionIndex]['options'][] = ['text' => ''];
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        unset($this->questions[$questionIndex]['options'][$optionIndex]);
        $this->questions[$questionIndex]['options'] = array_values($this->questions[$questionIndex]['options']);
        $this->questions[$questionIndex]['correct_option'] = 0;
    }

    public function save(): void
    {
        $exam = $this->persist();

        $this->toast()->success('Exam draft saved.')->flash()->send();
        $this->redirectRoute('lecturer.exams.edit', $exam, navigate: true);
    }

    public function publish(ExamPublicationService $publisher): void
    {
        $exam = $this->persist();
        $publisher->publish($exam);

        $this->toast()->success('Exam saved and published.')->flash()->send();
        $this->redirectRoute('lecturer.exams.edit', $exam, navigate: true);
    }

    private function persist(): Exam
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,open_text'],
            'questions.*.prompt' => ['required', 'string', 'max:5000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $assignment = TeachingAssignment::query()
            ->with(['schoolClass.subjects', 'subject'])
            ->findOrFail((int) $this->teaching_assignment_id);

        abort_unless($assignment->isOwnedBy(auth()->user()), 403);

        $this->school_class_id = (string) $assignment->school_class_id;
        $this->subject_id = (string) $assignment->subject_id;

        foreach ($this->questions as $index => $question) {
            if ($question['type'] !== QuestionType::MultipleChoice->value) {
                continue;
            }

            $options = collect($question['options'] ?? [])->filter(fn ($option) => filled($option['text'] ?? null));

            if ($options->count() < 2) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => __('Multiple-choice questions need at least two options.'),
                ]);
            }
        }

        if ($this->examId !== null) {
            $existing = Exam::query()->with('teachingAssignment')->findOrFail($this->examId);
            abort_unless(auth()->user()->can('update', $existing), 403);

            if ($existing->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'exam' => __('This exam already has submissions, so its questions can no longer be changed.'),
                ]);
            }
        }

        return DB::transaction(function () use ($assignment): Exam {
            $creating = $this->examId === null;

            $exam = Exam::query()->updateOrCreate(
                ['id' => $this->examId],
                [
                    'lecturer_id' => $assignment->lecturer_id,
                    'teaching_assignment_id' => $assignment->id,
                    'school_class_id' => $assignment->school_class_id,
                    'subject_id' => $assignment->subject_id,
                    'title' => $this->title,
                    'instructions' => $this->instructions ?: null,
                    'duration_minutes' => $this->duration_minutes,
                    'available_from' => filled($this->available_from) ? Carbon::parse($this->available_from) : null,
                    'available_until' => filled($this->available_until) ? Carbon::parse($this->available_until) : null,
                    'status' => ExamStatus::Draft,
                    'published_at' => null,
                    'closed_at' => null,
                ],
            );

            $exam->questions()->delete();

            foreach (array_values($this->questions) as $questionIndex => $questionData) {
                $question = Question::query()->create([
                    'exam_id' => $exam->id,
                    'type' => $questionData['type'],
                    'prompt' => $questionData['prompt'],
                    'points' => (int) $questionData['points'],
                    'position' => $questionIndex + 1,
                ]);

                if ($questionData['type'] !== QuestionType::MultipleChoice->value) {
                    continue;
                }

                foreach (array_values($questionData['options']) as $optionIndex => $optionData) {
                    if (blank($optionData['text'] ?? null)) {
                        continue;
                    }

                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'text' => $optionData['text'],
                        'is_correct' => (int) ($questionData['correct_option'] ?? 0) === $optionIndex,
                        'position' => $optionIndex + 1,
                    ]);
                }
            }

            $this->examId = $exam->id;

            app(AuditLogger::class)->record(
                $creating ? 'exam.created' : 'exam.updated',
                ($creating ? 'Created' : 'Updated').' draft exam '.$exam->title.'.',
                $exam,
                ['question_count' => count($this->questions)],
            );

            return $exam->fresh(['questions.options', 'schoolClass.subjects', 'teachingAssignment']);
        });
    }

    private function blankQuestion(string $type): array
    {
        return [
            'type' => $type,
            'prompt' => '',
            'points' => 1,
            'correct_option' => 0,
            'options' => $type === QuestionType::MultipleChoice->value ? [['text' => ''], ['text' => '']] : [],
        ];
    }

    public function assignment()
    {
        return filled($this->teaching_assignment_id)
            ? TeachingAssignment::query()
                ->with(['schoolClass', 'subject'])
                ->find($this->teaching_assignment_id)
            : null;
    }

    public function render(): View
    {
        return view('livewire.lecturer.exams.builder');
    }
}
