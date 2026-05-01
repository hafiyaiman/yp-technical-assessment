<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamPublicationService;
use App\Services\Exams\OpenTextGradingService;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

function createAssignedExamFixture(): array
{
    $lecturer = User::factory()->lecturer()->create();
    $student = User::factory()->student()->create();
    $class = SchoolClass::factory()->create();
    $subject = Subject::factory()->create();

    $class->subjects()->attach($subject);
    $student->update(['school_class_id' => $class->id]);

    $assignment = TeachingAssignment::factory()->create([
        'lecturer_id' => $lecturer->id,
        'school_class_id' => $class->id,
        'subject_id' => $subject->id,
    ]);

    $exam = Exam::factory()->forAssignment($assignment)->published()->create([
        'duration_minutes' => 15,
    ]);

    $multipleChoice = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => QuestionType::MultipleChoice,
        'prompt' => 'What is 2 + 2?',
        'points' => 2,
        'position' => 1,
    ]);

    $wrongOption = QuestionOption::factory()->create([
        'question_id' => $multipleChoice->id,
        'text' => '3',
        'is_correct' => false,
        'position' => 1,
    ]);

    $correctOption = QuestionOption::factory()->correct()->create([
        'question_id' => $multipleChoice->id,
        'text' => '4',
        'position' => 2,
    ]);

    $openText = Question::factory()->openText()->create([
        'exam_id' => $exam->id,
        'prompt' => 'Explain your answer.',
        'points' => 3,
        'position' => 2,
    ]);

    return compact('lecturer', 'student', 'class', 'subject', 'assignment', 'exam', 'multipleChoice', 'wrongOption', 'correctOption', 'openText');
}

test('admin can create subjects and classes with student assignments', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin);

    Volt::test('admin.subjects.index')
        ->set('name', 'Mathematics')
        ->set('code', 'MATH')
        ->set('description', 'Numbers and patterns')
        ->call('save')
        ->assertHasNoErrors();

    $subject = Subject::query()->where('code', 'MATH')->firstOrFail();

    Volt::test('admin.classes.index')
        ->set('name', 'Class 4A')
        ->set('code', 'CLASS-4A')
        ->set('subjectIds', [$subject->id])
        ->set('studentIds', [$student->id])
        ->call('save')
        ->assertHasNoErrors();

    $class = SchoolClass::query()->where('code', 'CLASS-4A')->firstOrFail();

    expect($class->subjects()->whereKey($subject->id)->exists())->toBeTrue();
    expect($student->fresh()->school_class_id)->toBe($class->id);
});

test('admin can create lecturer and student users', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $class = SchoolClass::factory()->create();

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->set('name', 'New Lecturer')
        ->set('email', 'new-lecturer@example.com')
        ->set('password', 'password')
        ->set('role', 'lecturer')
        ->call('save')
        ->assertHasNoErrors();

    Volt::test('admin.users.index')
        ->set('name', 'New Student')
        ->set('email', 'new-student-user@example.com')
        ->set('password', 'password')
        ->set('role', 'student')
        ->set('school_class_id', (string) $class->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'new-lecturer@example.com')->firstOrFail()->hasRole('lecturer'))->toBeTrue();
    expect(User::query()->where('email', 'new-student-user@example.com')->firstOrFail()->school_class_id)->toBe($class->id);
});

test('admin can assign lecturers to class subject pairs', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $lecturer = User::factory()->lecturer()->create();
    $class = SchoolClass::factory()->create();
    $subject = Subject::factory()->create();
    $class->subjects()->attach($subject);

    $this->actingAs($admin);

    Volt::test('admin.teaching-assignments.index')
        ->set('lecturer_id', (string) $lecturer->id)
        ->set('school_class_id', (string) $class->id)
        ->set('subject_id', (string) $subject->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(TeachingAssignment::query()
        ->where('lecturer_id', $lecturer->id)
        ->where('school_class_id', $class->id)
        ->where('subject_id', $subject->id)
        ->exists())->toBeTrue();
});

test('lecturer can build a draft exam with multiple-choice and open-text questions', function (): void {
    $lecturer = User::factory()->lecturer()->create();
    $class = SchoolClass::factory()->create();
    $subject = Subject::factory()->create();
    $class->subjects()->attach($subject);
    $assignment = TeachingAssignment::factory()->create([
        'lecturer_id' => $lecturer->id,
        'school_class_id' => $class->id,
        'subject_id' => $subject->id,
    ]);

    $this->actingAs($lecturer);

    Volt::test('lecturer.exams.builder', ['assignment' => $assignment])
        ->set('title', 'Midterm Paper A')
        ->set('instructions', 'Answer carefully.')
        ->set('duration_minutes', 15)
        ->set('questions', [
            [
                'type' => QuestionType::MultipleChoice->value,
                'prompt' => 'Choose the correct answer.',
                'points' => 2,
                'correct_option' => 1,
                'options' => [
                    ['text' => 'Wrong'],
                    ['text' => 'Correct'],
                ],
            ],
            [
                'type' => QuestionType::OpenText->value,
                'prompt' => 'Explain your reasoning.',
                'points' => 3,
                'correct_option' => 0,
                'options' => [],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $exam = Exam::query()->where('title', 'Midterm Paper A')->firstOrFail();

    expect($exam->teaching_assignment_id)->toBe($assignment->id);
    expect($exam->questions()->count())->toBe(2);
    expect($exam->questions()->where('type', QuestionType::MultipleChoice->value)->firstOrFail()->options()->where('is_correct', true)->count())->toBe(1);
});

test('lecturer cannot access admin academic setup pages', function (): void {
    $lecturer = User::factory()->lecturer()->create();

    $this->actingAs($lecturer)->get(route('admin.classes.index'))->assertForbidden();
    $this->actingAs($lecturer)->get(route('admin.subjects.index'))->assertForbidden();
    $this->actingAs($lecturer)->get(route('admin.users.index'))->assertForbidden();
});

test('lecturer sees only assigned exams and cannot manage another lecturer exam', function (): void {
    $fixture = createAssignedExamFixture();
    $otherLecturer = User::factory()->lecturer()->create();

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.exams.submissions', $fixture['exam']))
        ->assertForbidden();

    $this->actingAs($otherLecturer);

    Volt::test('lecturer.exams.index')
        ->assertDontSee($fixture['exam']->title);
});

test('publishing fails when an exam has invalid questions', function (): void {
    $fixture = createAssignedExamFixture();
    $exam = $fixture['exam'];
    $exam->questions()->delete();

    app(ExamPublicationService::class)->publish($exam);
})->throws(ValidationException::class);

test('students only access published exams assigned to their class', function (): void {
    $fixture = createAssignedExamFixture();
    $otherStudent = User::factory()->student()->create([
        'school_class_id' => SchoolClass::factory()->create()->id,
    ]);

    $this->actingAs($fixture['student'])
        ->get(route('student.exams.show', $fixture['exam']))
        ->assertOk()
        ->assertSee('Start Exam');

    $this->actingAs($otherStudent)
        ->get(route('student.exams.show', $fixture['exam']))
        ->assertForbidden();
});

test('student gets one attempt with autosaved answers and open-text review', function (): void {
    $fixture = createAssignedExamFixture();
    $service = app(ExamAttemptService::class);

    $attempt = $service->start($fixture['student'], $fixture['exam']->fresh());
    $sameAttempt = $service->start($fixture['student'], $fixture['exam']->fresh());

    expect($sameAttempt->id)->toBe($attempt->id);

    $service->saveAnswer($attempt, $fixture['multipleChoice']->id, $fixture['correctOption']->id);
    $service->saveAnswer($attempt, $fixture['openText']->id, 'Because 2 and 2 make 4.');

    $submitted = $service->submit($attempt, [
        $fixture['multipleChoice']->id => $fixture['correctOption']->id,
        $fixture['openText']->id => 'Because 2 and 2 make 4.',
    ]);

    expect($submitted->status)->toBe(ExamAttemptStatus::Submitted);
    expect($submitted->score)->toBe(2);
    expect($submitted->max_score)->toBe(5);

    $openAnswer = $submitted->answers->firstWhere('question_id', $fixture['openText']->id);
    $graded = app(OpenTextGradingService::class)->grade($openAnswer, 3, 'Good explanation.');

    expect($graded->status)->toBe(ExamAttemptStatus::Graded);
    expect($graded->score)->toBe(5);
});

test('expired attempts cannot be submitted as valid results', function (): void {
    $fixture = createAssignedExamFixture();
    $service = app(ExamAttemptService::class);

    $attempt = $service->start($fixture['student'], $fixture['exam']->fresh());
    $attempt->update(['expires_at' => now()->subMinute()]);

    $submitted = $service->submit($attempt, [
        $fixture['multipleChoice']->id => $fixture['correctOption']->id,
    ]);

    expect($submitted->status)->toBe(ExamAttemptStatus::Expired);
    expect($submitted->score)->toBe(0);
});
