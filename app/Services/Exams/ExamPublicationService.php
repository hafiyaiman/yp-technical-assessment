<?php

namespace App\Services\Exams;

use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamPublicationService
{
    public function publish(Exam $exam): Exam
    {
        $exam->loadMissing(['questions.options', 'schoolClass.subjects', 'teachingAssignment']);

        $this->validateCanPublish($exam);

        return DB::transaction(function () use ($exam): Exam {
            $exam->update([
                'status' => ExamStatus::Published,
                'published_at' => $exam->published_at ?? now(),
                'closed_at' => null,
            ]);

            return $exam->fresh(['questions.options', 'schoolClass', 'subject']);
        });
    }

    public function close(Exam $exam): Exam
    {
        $exam->update([
            'status' => ExamStatus::Closed,
            'closed_at' => now(),
        ]);

        return $exam->fresh();
    }

    private function validateCanPublish(Exam $exam): void
    {
        $errors = [];

        if (blank($exam->title)) {
            $errors['title'] = __('The exam title is required.');
        }

        if ($exam->school_class_id === null) {
            $errors['school_class_id'] = __('A class is required.');
        }

        if ($exam->teaching_assignment_id === null) {
            $errors['teaching_assignment_id'] = __('A teaching assignment is required.');
        }

        if ($exam->subject_id === null) {
            $errors['subject_id'] = __('A subject is required.');
        }

        if ($exam->teachingAssignment !== null
            && ($exam->teachingAssignment->school_class_id !== $exam->school_class_id
                || $exam->teachingAssignment->subject_id !== $exam->subject_id
                || $exam->teachingAssignment->lecturer_id !== $exam->lecturer_id)) {
            $errors['teaching_assignment_id'] = __('The exam must match its teaching assignment.');
        }

        if ($exam->duration_minutes < 1) {
            $errors['duration_minutes'] = __('The exam duration must be at least 1 minute.');
        }

        if ($exam->schoolClass !== null && ! $exam->schoolClass->subjects->contains('id', $exam->subject_id)) {
            $errors['subject_id'] = __('The selected subject must be assigned to the selected class.');
        }

        if ($exam->questions->isEmpty()) {
            $errors['questions'] = __('Add at least one question before publishing.');
        }

        foreach ($exam->questions as $question) {
            if (blank($question->prompt)) {
                $errors["question_{$question->id}"] = __('Each question needs a prompt.');
            }

            if ($question->points < 1) {
                $errors["question_points_{$question->id}"] = __('Each question must be worth at least 1 point.');
            }

            if ($question->type === QuestionType::MultipleChoice) {
                $optionCount = $question->options->count();
                $correctCount = $question->options->where('is_correct', true)->count();

                if ($optionCount < 2) {
                    $errors["question_options_{$question->id}"] = __('Multiple-choice questions need at least two options.');
                }

                if ($correctCount !== 1) {
                    $errors["question_correct_{$question->id}"] = __('Multiple-choice questions need exactly one correct option.');
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
