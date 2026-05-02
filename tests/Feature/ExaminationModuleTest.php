<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\AuditLog;
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
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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

    Livewire::test('admin.subjects.index')
        ->set('name', 'Mathematics')
        ->set('code', 'MATH')
        ->set('description', 'Numbers and patterns')
        ->call('save')
        ->assertHasNoErrors();

    $subject = Subject::query()->where('code', 'MATH')->firstOrFail();

    Livewire::test('admin.classes.index')
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

test('admin dashboard shows setup health and audit logs', function (): void {
    $admin = User::factory()->systemAdmin()->create();

    $this->actingAs($admin);

    Livewire::test('admin.subjects.index')
        ->set('name', 'Audit Mathematics')
        ->set('code', 'AUD-MATH')
        ->call('save')
        ->assertHasNoErrors();

    expect(AuditLog::query()->where('action', 'subject.created')->exists())->toBeTrue();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Setup Health')
        ->assertSee('Audit Logs')
        ->assertSee('Created subject Audit Mathematics.');
});

test('admin can open and filter audit logs page', function (): void {
    $admin = User::factory()->systemAdmin()->create();

    AuditLog::query()->create([
        'actor_id' => $admin->id,
        'action' => 'class.created',
        'description' => 'Created class Audit Room.',
    ]);

    AuditLog::query()->create([
        'actor_id' => $admin->id,
        'action' => 'subject.created',
        'description' => 'Created subject Audit Science.',
    ]);

    $this->actingAs($admin);

    $this->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Logs')
        ->assertSee('Created class Audit Room.')
        ->assertSee('Created subject Audit Science.');

    Livewire::test('admin.audit-logs.index')
        ->set('search', 'Room')
        ->assertSee('Created class Audit Room.')
        ->assertDontSee('Created subject Audit Science.')
        ->set('search', '')
        ->set('actionFilters', ['subject.created'])
        ->assertSee('Created subject Audit Science.')
        ->assertDontSee('Created class Audit Room.');
});

test('class wizard requires details and subjects before moving forward', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin);

    Livewire::test('admin.classes.index')
        ->call('nextClassStep')
        ->assertHasErrors(['name' => 'required', 'code' => 'required'])
        ->assertSet('classStep', '1')
        ->set('name', 'Class 4B')
        ->set('code', 'CLASS-4B')
        ->call('nextClassStep')
        ->assertHasNoErrors(['name', 'code'])
        ->assertSet('classStep', '2')
        ->call('nextClassStep')
        ->assertHasErrors(['subjectIds' => 'required'])
        ->assertSet('classStep', '2')
        ->set('subjectIds', [$subject->id])
        ->call('nextClassStep')
        ->assertHasNoErrors(['subjectIds'])
        ->assertSet('classStep', '3')
        ->call('nextClassStep')
        ->assertHasNoErrors(['studentIds'])
        ->assertSet('classStep', '4');
});

test('admin can create lecturer and student users', function (): void {
    Notification::fake();

    $admin = User::factory()->systemAdmin()->create();
    $class = SchoolClass::factory()->create();

    $this->actingAs($admin);

    Livewire::test('admin.users.index')
        ->set('name', 'New Lecturer')
        ->set('email', 'new-lecturer@example.com')
        ->set('role', 'lecturer')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test('admin.users.index')
        ->set('name', 'New Student')
        ->set('email', 'new-student-user@example.com')
        ->set('role', 'student')
        ->set('school_class_id', (string) $class->id)
        ->call('save')
        ->assertHasNoErrors();

    $lecturer = User::query()->where('email', 'new-lecturer@example.com')->firstOrFail();
    $student = User::query()->where('email', 'new-student-user@example.com')->firstOrFail();

    expect($lecturer->hasRole('lecturer'))->toBeTrue();
    expect($student->school_class_id)->toBe($class->id);

    Notification::assertSentTo($lecturer, UserInvitation::class, function (UserInvitation $notification) use ($lecturer): bool {
        $mail = $notification->toMail($lecturer);

        expect($mail->subject)->toBe('You are invited to Exam Portal');
        expect($mail->actionText)->toBe('Set Password');

        return true;
    });
    Notification::assertSentTo($student, UserInvitation::class);
});

test('admin can assign lecturers to class subject pairs', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $lecturer = User::factory()->lecturer()->create();
    $class = SchoolClass::factory()->create();
    $subject = Subject::factory()->create();
    $class->subjects()->attach($subject);

    $this->actingAs($admin);

    Livewire::test('admin.teaching-assignments.index')
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

test('admin can filter users by search role class and subject', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $math = Subject::factory()->create(['name' => 'Algebra Prime', 'code' => 'ALG']);
    $english = Subject::factory()->create(['name' => 'Biology Prime', 'code' => 'BIO']);
    $classA = SchoolClass::factory()->create(['name' => 'Room Alpha', 'code' => 'ROOM-A']);
    $classB = SchoolClass::factory()->create(['name' => 'Room Beta', 'code' => 'ROOM-B']);
    $classA->subjects()->attach($math);
    $classB->subjects()->attach($english);

    $lecturerA = User::factory()->lecturer()->create(['name' => 'Alpha Lecturer', 'email' => 'alpha-lecturer@example.com']);
    $lecturerB = User::factory()->lecturer()->create(['name' => 'Beta Lecturer', 'email' => 'beta-lecturer@example.com']);
    $studentA = User::factory()->student()->create(['name' => 'Alpha Student', 'email' => 'alpha-student@example.com', 'school_class_id' => $classA->id]);
    $studentB = User::factory()->student()->create(['name' => 'Beta Student', 'email' => 'beta-student@example.com', 'school_class_id' => $classB->id]);

    TeachingAssignment::factory()->create([
        'lecturer_id' => $lecturerA->id,
        'school_class_id' => $classA->id,
        'subject_id' => $math->id,
    ]);
    TeachingAssignment::factory()->create([
        'lecturer_id' => $lecturerB->id,
        'school_class_id' => $classB->id,
        'subject_id' => $english->id,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.users.index')
        ->set('search', 'Alpha')
        ->assertSee('Alpha Lecturer')
        ->assertSee('Alpha Student')
        ->assertDontSee('Beta Lecturer')
        ->set('search', '')
        ->set('roleFilters', ['student'])
        ->assertSee('Alpha Student')
        ->assertSee('Beta Student')
        ->assertDontSee('Alpha Lecturer')
        ->set('roleFilters', [])
        ->set('classFilters', [$classA->id])
        ->assertSee('Alpha Lecturer')
        ->assertSee('Alpha Student')
        ->assertDontSee('Beta Student')
        ->set('classFilters', [])
        ->set('subjectFilters', [$math->id])
        ->assertSee('Alpha Lecturer')
        ->assertDontSee('Alpha Student')
        ->assertDontSee('Beta Lecturer');
});

test('admin can manage lecturer teaching assignments from users table', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $lecturer = User::factory()->lecturer()->create();
    $classA = SchoolClass::factory()->create();
    $classB = SchoolClass::factory()->create();
    $math = Subject::factory()->create();
    $english = Subject::factory()->create();
    $classA->subjects()->attach($math);
    $classB->subjects()->attach($english);

    $oldAssignment = TeachingAssignment::factory()->create([
        'lecturer_id' => $lecturer->id,
        'school_class_id' => $classA->id,
        'subject_id' => $math->id,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.users.index')
        ->call('manageTeaching', $lecturer->id)
        ->set('teachingAssignmentKeys', ["{$classB->id}:{$english->id}"])
        ->call('saveTeachingAssignments')
        ->assertHasNoErrors();

    expect(TeachingAssignment::query()->whereKey($oldAssignment->id)->exists())->toBeFalse();
    expect(TeachingAssignment::query()
        ->where('lecturer_id', $lecturer->id)
        ->where('school_class_id', $classB->id)
        ->where('subject_id', $english->id)
        ->exists())->toBeTrue();
});

test('admin can search and filter classes and subjects', function (): void {
    $admin = User::factory()->systemAdmin()->create();
    $math = Subject::factory()->create(['name' => 'Algebra Prime', 'code' => 'ALG']);
    $english = Subject::factory()->create(['name' => 'Biology Prime', 'code' => 'BIO']);
    $classA = SchoolClass::factory()->create(['name' => 'Room Alpha', 'code' => 'ROOM-A']);
    $classB = SchoolClass::factory()->create(['name' => 'Room Beta', 'code' => 'ROOM-B']);
    $classA->subjects()->attach($math);
    $classB->subjects()->attach($english);

    $this->actingAs($admin);

    Livewire::test('admin.classes.index')
        ->set('search', 'Alpha')
        ->assertSee('Room Alpha')
        ->assertDontSee('Room Beta')
        ->set('search', '')
        ->set('subjectFilters', [$english->id])
        ->assertSee('Room Beta')
        ->assertDontSee('Room Alpha');

    Livewire::test('admin.subjects.index')
        ->set('search', 'Algebra')
        ->assertSee('Algebra Prime')
        ->assertDontSee('Biology Prime')
        ->set('search', '')
        ->set('classFilters', [$classB->id])
        ->assertSee('Biology Prime')
        ->assertDontSee('Algebra Prime');
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

    Livewire::test('lecturer.exams.builder', ['assignment' => $assignment])
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
    expect(AuditLog::query()->where('action', 'exam.created')->where('subject_id', $exam->id)->exists())->toBeTrue();

    Livewire::test('lecturer.exams.activity', ['exam' => $exam])
        ->assertSee('Activity History')
        ->assertSee('Created draft exam Midterm Paper A.');
});

test('lecturer cannot access admin academic setup pages', function (): void {
    $lecturer = User::factory()->lecturer()->create();

    $this->actingAs($lecturer)->get(route('admin.classes.index'))->assertForbidden();
    $this->actingAs($lecturer)->get(route('admin.subjects.index'))->assertForbidden();
    $this->actingAs($lecturer)->get(route('admin.users.index'))->assertForbidden();
});

test('lecturer sidebar only shows class and exam workflow links', function (): void {
    $lecturer = User::factory()->lecturer()->create();

    $this->actingAs($lecturer)
        ->get(route('lecturer.teaching.index'))
        ->assertOk()
        ->assertSee('My Classes')
        ->assertSee('Exams')
        ->assertDontSee('Marking')
        ->assertDontSee('Results');
});

test('lecturer can open assigned class detail and only see enrolled class students', function (): void {
    $fixture = createAssignedExamFixture();
    $otherStudent = User::factory()->student()->create([
        'name' => 'Other Class Student',
        'school_class_id' => SchoolClass::factory()->create()->id,
    ]);

    $this->actingAs($fixture['lecturer'])
        ->get(route('lecturer.teaching.show', $fixture['assignment']))
        ->assertOk()
        ->assertSee($fixture['student']->name)
        ->assertSee($fixture['exam']->title)
        ->assertDontSee($otherStudent->name);
});

test('lecturer can open owned exam overview and results only', function (): void {
    $fixture = createAssignedExamFixture();
    $otherLecturer = User::factory()->lecturer()->create();

    $this->actingAs($fixture['lecturer']);

    $this->get(route('lecturer.exams.show', $fixture['exam']))
        ->assertOk()
        ->assertSee($fixture['exam']->title)
        ->assertSee('Exam Details')
        ->assertSee('Activity History');

    $this->get(route('lecturer.exams.results', $fixture['exam']))
        ->assertOk()
        ->assertSee('Student Results');

    $this->get(route('lecturer.exams.activity', $fixture['exam']))
        ->assertOk()
        ->assertSee('Activity History');

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.teaching.show', $fixture['assignment']))
        ->assertForbidden();

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.exams.show', $fixture['exam']))
        ->assertForbidden();

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.exams.results', $fixture['exam']))
        ->assertForbidden();

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.exams.activity', $fixture['exam']))
        ->assertForbidden();
});

test('lecturer sees only assigned exams and cannot manage another lecturer exam', function (): void {
    $fixture = createAssignedExamFixture();
    $otherLecturer = User::factory()->lecturer()->create();

    $this->actingAs($otherLecturer)
        ->get(route('lecturer.exams.submissions', $fixture['exam']))
        ->assertForbidden();

    $this->actingAs($otherLecturer);

    Livewire::test('lecturer.exams.index')
        ->set('search', $fixture['exam']->title)
        ->assertDontSee($fixture['exam']->title);
});

test('lecturer exams index supports title search and exam scoped actions', function (): void {
    $fixture = createAssignedExamFixture();
    $otherExam = Exam::factory()->forAssignment($fixture['assignment'])->published()->create(['title' => 'Hidden Paper']);

    $this->actingAs($fixture['lecturer']);

    Livewire::test('lecturer.exams.index')
        ->set('search', $fixture['exam']->title)
        ->assertSee($fixture['exam']->title)
        ->assertDontSee($otherExam->title)
        ->assertSee('View Overview')
        ->assertSee('Submissions')
        ->assertSee('Results')
        ->set('search', 'Hidden')
        ->assertSee($otherExam->title)
        ->assertDontSee($fixture['exam']->title);
});

test('lecturer creates exam by choosing an assigned class subject', function (): void {
    $fixture = createAssignedExamFixture();
    $otherLecturer = User::factory()->lecturer()->create();
    $otherClass = SchoolClass::factory()->create(['name' => 'Other Class']);
    $otherSubject = Subject::factory()->create(['name' => 'Other Subject']);
    $otherClass->subjects()->attach($otherSubject);
    TeachingAssignment::factory()->create([
        'lecturer_id' => $otherLecturer->id,
        'school_class_id' => $otherClass->id,
        'subject_id' => $otherSubject->id,
    ]);

    $this->actingAs($fixture['lecturer']);

    $this->get(route('lecturer.exams.index'))
        ->assertOk()
        ->assertSee('Create Exam');

    Livewire::test('lecturer.exams.index')
        ->call('openCreateModal')
        ->assertSet('modal', true)
        ->assertSee('Choose Class &amp; Subject', false)
        ->assertSee($fixture['class']->name)
        ->assertSee($fixture['subject']->name)
        ->assertDontSee('Other Class')
        ->assertDontSee('Other Subject')
        ->set('createSearch', $fixture['class']->name)
        ->assertSee($fixture['class']->name)
        ->assertDontSee('Other Class');
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
    expect(AuditLog::query()->where('action', 'exam_attempt.started')->where('subject_id', $fixture['exam']->id)->exists())->toBeTrue();
    expect(AuditLog::query()->where('action', 'exam_attempt.submitted')->where('subject_id', $fixture['exam']->id)->exists())->toBeTrue();

    $openAnswer = $submitted->answers->firstWhere('question_id', $fixture['openText']->id);
    $graded = app(OpenTextGradingService::class)->grade($openAnswer, 3, 'Good explanation.');

    expect($graded->status)->toBe(ExamAttemptStatus::Graded);
    expect($graded->score)->toBe(5);

    $this->actingAs($fixture['lecturer']);

    Livewire::test('lecturer.exams.activity', ['exam' => $fixture['exam']])
        ->assertSee('Activity History')
        ->assertSee($fixture['student']->name.' started '.$fixture['exam']->title.'.')
        ->assertSee($fixture['student']->name.' submitted '.$fixture['exam']->title.'.');
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
