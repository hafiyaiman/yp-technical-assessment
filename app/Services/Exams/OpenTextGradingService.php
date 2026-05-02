<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenTextGradingService
{
    public function grade(ExamAnswer $answer, int $points, ?string $feedback = null): ExamAttempt
    {
        $answer->loadMissing(['question', 'attempt.exam.questions', 'attempt.answers.question']);

        if ($answer->question->type !== QuestionType::OpenText) {
            throw ValidationException::withMessages([
                'answer' => __('Only open-text answers can be graded manually.'),
            ]);
        }

        if ($points < 0 || $points > $answer->question->points) {
            throw ValidationException::withMessages([
                "points.{$answer->id}" => __('Awarded marks must be within the question mark value.'),
            ]);
        }

        return DB::transaction(function () use ($answer, $points, $feedback): ExamAttempt {
            $answer->update([
                'points_awarded' => $points,
                'is_correct' => $points === $answer->question->points,
                'feedback' => $feedback,
            ]);

            $attempt = $answer->attempt->fresh(['exam.questions', 'answers.question']);
            $openQuestionIds = $attempt->exam->questions
                ->where('type', QuestionType::OpenText)
                ->pluck('id');

            $gradedOpenAnswers = $attempt->answers
                ->whereIn('question_id', $openQuestionIds)
                ->whereNotNull('is_correct');

            $score = $attempt->answers->sum('points_awarded');
            $maxScore = $attempt->exam->questions->sum('points');

            $attempt->update([
                'score' => $score,
                'max_score' => $maxScore,
                'status' => $gradedOpenAnswers->count() === $openQuestionIds->count()
                    ? ExamAttemptStatus::Graded
                    : $attempt->status,
                'graded_at' => $gradedOpenAnswers->count() === $openQuestionIds->count()
                    ? now()
                    : $attempt->graded_at,
            ]);

            return $attempt->fresh(['answers.question', 'student', 'exam']);
        });
    }
}
