<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function start(User $student, Exam $exam): ExamAttempt
    {
        if (! $exam->canBeTakenBy($student)) {
            throw new AuthorizationException(__('This exam is not available for your class.'));
        }

        return DB::transaction(function () use ($student, $exam): ExamAttempt {
            $attempt = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($attempt !== null) {
                if ($attempt->status === ExamAttemptStatus::InProgress && $attempt->isExpired()) {
                    $attempt->update(['status' => ExamAttemptStatus::Expired]);
                }

                return $attempt->fresh(['exam.questions.options', 'answers']);
            }

            $attempt = ExamAttempt::query()->create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => ExamAttemptStatus::InProgress,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($exam->duration_minutes),
                'score' => 0,
                'max_score' => $exam->questions()->sum('points'),
            ]);

            app(AuditLogger::class)->record(
                'exam_attempt.started',
                $student->name.' started '.$exam->title.'.',
                $exam,
                ['attempt_id' => $attempt->id, 'student_id' => $student->id],
            );

            return $attempt->fresh(['exam.questions.options', 'answers']);
        });
    }

    public function saveAnswer(ExamAttempt $attempt, int $questionId, mixed $answer): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $questionId, $answer): ExamAttempt {
            $attempt = ExamAttempt::query()
                ->with(['exam.questions.options'])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($attempt->status !== ExamAttemptStatus::InProgress || $attempt->isExpired()) {
                return $attempt;
            }

            $question = $attempt->exam->questions->firstWhere('id', $questionId);

            if ($question === null) {
                return $attempt;
            }

            if ($question->type === QuestionType::MultipleChoice) {
                $option = filled($answer)
                    ? QuestionOption::query()
                        ->where('question_id', $question->id)
                        ->find((int) $answer)
                    : null;

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'question_option_id' => $option?->id,
                        'open_text_answer' => null,
                        'points_awarded' => 0,
                        'is_correct' => null,
                        'feedback' => null,
                    ],
                );

                return $attempt->fresh(['answers', 'exam.questions.options']);
            }

            $attempt->answers()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'question_option_id' => null,
                    'open_text_answer' => filled($answer) ? (string) $answer : null,
                    'points_awarded' => 0,
                    'is_correct' => null,
                    'feedback' => null,
                ],
            );

            return $attempt->fresh(['answers', 'exam.questions.options']);
        });
    }

    /**
     * @param  array<int|string, mixed>  $answers
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers): ExamAttempt {
            $attempt = ExamAttempt::query()
                ->with(['exam.questions.options'])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($attempt->status !== ExamAttemptStatus::InProgress) {
                throw ValidationException::withMessages([
                    'attempt' => __('This exam attempt has already been submitted.'),
                ]);
            }

            $maxScore = $attempt->exam->questions->sum('points');

            if ($attempt->isExpired()) {
                $attempt->update([
                    'status' => ExamAttemptStatus::Expired,
                    'max_score' => $maxScore,
                ]);

                app(AuditLogger::class)->record(
                    'exam_attempt.expired',
                    $attempt->student->name.' attempt expired for '.$attempt->exam->title.'.',
                    $attempt->exam,
                    ['attempt_id' => $attempt->id, 'student_id' => $attempt->student_id],
                );

                return $attempt->fresh(['answers', 'exam.questions.options']);
            }

            $score = 0;
            $hasOpenText = false;

            foreach ($attempt->exam->questions as $question) {
                $answer = $answers[$question->id] ?? null;

                if ($question->type === QuestionType::MultipleChoice) {
                    $option = $answer !== null
                        ? QuestionOption::query()
                            ->where('question_id', $question->id)
                            ->find((int) $answer)
                        : null;

                    $isCorrect = $option?->is_correct === true;
                    $points = $isCorrect ? $question->points : 0;
                    $score += $points;

                    $attempt->answers()->updateOrCreate(
                        ['question_id' => $question->id],
                        [
                            'question_option_id' => $option?->id,
                            'open_text_answer' => null,
                            'points_awarded' => $points,
                            'is_correct' => $isCorrect,
                            'feedback' => null,
                        ],
                    );

                    continue;
                }

                $hasOpenText = true;

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'question_option_id' => null,
                        'open_text_answer' => filled($answer) ? (string) $answer : null,
                        'points_awarded' => 0,
                        'is_correct' => null,
                        'feedback' => null,
                    ],
                );
            }

            $attempt->update([
                'status' => $hasOpenText ? ExamAttemptStatus::Submitted : ExamAttemptStatus::Graded,
                'submitted_at' => now(),
                'graded_at' => $hasOpenText ? null : now(),
                'score' => $score,
                'max_score' => $maxScore,
            ]);

            app(AuditLogger::class)->record(
                $hasOpenText ? 'exam_attempt.submitted' : 'exam_attempt.auto_graded',
                $attempt->student->name.' submitted '.$attempt->exam->title.'.',
                $attempt->exam,
                ['attempt_id' => $attempt->id, 'student_id' => $attempt->student_id],
            );

            return $attempt->fresh(['answers.question', 'exam.questions.options']);
        });
    }
}
