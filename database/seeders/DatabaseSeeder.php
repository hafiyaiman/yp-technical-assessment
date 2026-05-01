<?php

namespace Database\Seeders;

use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'System Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('system-admin');

        $lecturers = collect([
            $this->user('lecturer@example.com', 'Lecturer User', 'lecturer'),
        ]);

        for ($number = 1; $number <= 6; $number++) {
            $lecturers->push($this->user(
                "lecturer{$number}@example.com",
                "Lecturer {$number}",
                'lecturer',
            ));
        }

        $classes = collect([
            ['code' => 'CLASS-1A', 'name' => 'Class 1A', 'subjects' => ['MATH', 'ENG', 'SCI']],
            ['code' => 'CLASS-2B', 'name' => 'Class 2B', 'subjects' => ['MATH', 'ENG', 'ICT']],
            ['code' => 'CLASS-3C', 'name' => 'Class 3C', 'subjects' => ['MATH', 'SCI', 'HIST', 'GEO']],
            ['code' => 'CLASS-4A', 'name' => 'Class 4A', 'subjects' => ['MATH', 'ENG', 'SCI', 'ICT']],
            ['code' => 'CLASS-5S', 'name' => 'Class 5 Science', 'subjects' => ['MATH', 'ENG', 'SCI', 'HIST']],
        ])->mapWithKeys(fn (array $class): array => [
            $class['code'] => SchoolClass::query()->updateOrCreate(
                ['code' => $class['code']],
                [
                    'name' => $class['name'],
                    'description' => "{$class['name']} demo cohort for the online examination portal.",
                ],
            ),
        ]);

        $subjects = collect([
            ['code' => 'MATH', 'name' => 'Mathematics', 'description' => 'Numbers, patterns, and problem solving.'],
            ['code' => 'ENG', 'name' => 'English', 'description' => 'Language, grammar, and writing.'],
            ['code' => 'SCI', 'name' => 'Science', 'description' => 'Scientific thinking, experiments, and concepts.'],
            ['code' => 'HIST', 'name' => 'History', 'description' => 'Source analysis, chronology, and historical context.'],
            ['code' => 'ICT', 'name' => 'Information Technology', 'description' => 'Digital literacy, systems, and practical computing.'],
            ['code' => 'GEO', 'name' => 'Geography', 'description' => 'Places, environments, and map skills.'],
        ])->mapWithKeys(fn (array $subject): array => [
            $subject['code'] => Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                ['name' => $subject['name'], 'description' => $subject['description']],
            ),
        ]);

        $classSubjectMap = [
            'CLASS-1A' => ['MATH', 'ENG', 'SCI'],
            'CLASS-2B' => ['MATH', 'ENG', 'ICT'],
            'CLASS-3C' => ['MATH', 'SCI', 'HIST', 'GEO'],
            'CLASS-4A' => ['MATH', 'ENG', 'SCI', 'ICT'],
            'CLASS-5S' => ['MATH', 'ENG', 'SCI', 'HIST'],
        ];

        foreach ($classSubjectMap as $classCode => $subjectCodes) {
            $classes[$classCode]->subjects()->sync(collect($subjectCodes)->map(fn (string $code) => $subjects[$code]->id)->all());
        }

        $this->user('student@example.com', 'Student User', 'student', $classes['CLASS-4A']->id);

        $classIds = $classes->values()->pluck('id')->all();

        for ($number = 1; $number <= 48; $number++) {
            $classId = $classIds[($number - 1) % count($classIds)];

            $this->user(
                "student{$number}@example.com",
                "Student {$number}",
                'student',
                $classId,
            );
        }

        $assignments = collect();
        $assignmentIndex = 0;

        foreach ($classSubjectMap as $classCode => $subjectCodes) {
            foreach ($subjectCodes as $subjectCode) {
                $lecturer = $lecturers[$assignmentIndex % $lecturers->count()];

                $assignments->put("{$classCode}:{$subjectCode}", TeachingAssignment::query()->updateOrCreate(
                    [
                        'lecturer_id' => $lecturer->id,
                        'school_class_id' => $classes[$classCode]->id,
                        'subject_id' => $subjects[$subjectCode]->id,
                    ],
                ));

                $assignmentIndex++;
            }
        }

        $this->exam($assignments['CLASS-4A:MATH'], [
            'title' => 'Mathematics Quick Check',
            'instructions' => 'Answer each question carefully. You will have 15 minutes once you start.',
            'duration_minutes' => 15,
            'status' => ExamStatus::Published,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'What is 8 x 7?',
                    'points' => 2,
                    'options' => ['54', '56', '64'],
                    'correct' => 1,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Explain how you checked your multiplication answer.',
                    'points' => 3,
                ],
            ],
        ]);

        $this->exam($assignments['CLASS-1A:ENG'], [
            'title' => 'English Grammar Sprint',
            'instructions' => 'Choose the best answer, then write one short explanation.',
            'duration_minutes' => 20,
            'status' => ExamStatus::Published,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'Which sentence uses the correct tense?',
                    'points' => 2,
                    'options' => ['She go to school.', 'She goes to school.', 'She going to school.'],
                    'correct' => 1,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Write a sentence using the word "although".',
                    'points' => 4,
                ],
            ],
        ]);

        $this->exam($assignments['CLASS-2B:ICT'], [
            'title' => 'ICT Practical Concepts',
            'instructions' => 'This draft exam is available for lecturer editing.',
            'duration_minutes' => 25,
            'status' => ExamStatus::Draft,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'Which device is used for long-term data storage?',
                    'points' => 2,
                    'options' => ['RAM', 'SSD', 'CPU'],
                    'correct' => 1,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Describe one safe password practice.',
                    'points' => 3,
                ],
            ],
        ]);

        $this->exam($assignments['CLASS-3C:SCI'], [
            'title' => 'Science Forces Review',
            'instructions' => 'Use examples from daily life where helpful.',
            'duration_minutes' => 30,
            'status' => ExamStatus::Published,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'Which force pulls objects toward Earth?',
                    'points' => 2,
                    'options' => ['Friction', 'Gravity', 'Magnetism'],
                    'correct' => 1,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Explain how friction can be useful.',
                    'points' => 5,
                ],
            ],
        ]);

        $this->exam($assignments['CLASS-3C:HIST'], [
            'title' => 'History Source Analysis',
            'instructions' => 'Read each source carefully before answering.',
            'duration_minutes' => 35,
            'status' => ExamStatus::Closed,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'What is a primary source?',
                    'points' => 2,
                    'options' => ['A first-hand record', 'A modern textbook summary', 'A fictional story'],
                    'correct' => 0,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Why should historians compare multiple sources?',
                    'points' => 5,
                ],
            ],
        ]);

        $this->exam($assignments['CLASS-5S:SCI'], [
            'title' => 'Science Extended Response',
            'instructions' => 'Open-text answers will be marked by your lecturer.',
            'duration_minutes' => 40,
            'status' => ExamStatus::Published,
            'questions' => [
                [
                    'type' => QuestionType::MultipleChoice,
                    'prompt' => 'Which process do plants use to make food?',
                    'points' => 2,
                    'options' => ['Photosynthesis', 'Condensation', 'Evaporation'],
                    'correct' => 0,
                ],
                [
                    'type' => QuestionType::OpenText,
                    'prompt' => 'Explain why sunlight is important to plants.',
                    'points' => 6,
                ],
            ],
        ]);
    }

    private function user(string $email, string $name, string $role, ?int $schoolClassId = null): User
    {
        $user = User::query()->updateOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'password' => Hash::make('password'),
            'school_class_id' => $role === 'student' ? $schoolClassId : null,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array{title: string, instructions: string, duration_minutes: int, status: ExamStatus, questions: array<int, array<string, mixed>>}  $data
     */
    private function exam(TeachingAssignment $assignment, array $data): Exam
    {
        $publishedAt = $data['status'] === ExamStatus::Published ? now()->subDay() : null;
        $closedAt = $data['status'] === ExamStatus::Closed ? now()->subDay() : null;

        $exam = Exam::query()->updateOrCreate(
            ['title' => $data['title'], 'school_class_id' => $assignment->school_class_id],
            [
                'lecturer_id' => $assignment->lecturer_id,
                'teaching_assignment_id' => $assignment->id,
                'subject_id' => $assignment->subject_id,
                'instructions' => $data['instructions'],
                'duration_minutes' => $data['duration_minutes'],
                'available_from' => now()->subWeek(),
                'available_until' => $data['status'] === ExamStatus::Closed ? now()->subDay() : now()->addMonth(),
                'status' => $data['status'],
                'published_at' => $publishedAt,
                'closed_at' => $closedAt,
            ],
        );

        if ($exam->attempts()->exists()) {
            return $exam;
        }

        $exam->questions()->delete();

        foreach ($data['questions'] as $position => $questionData) {
            $question = Question::query()->create([
                'exam_id' => $exam->id,
                'type' => $questionData['type'],
                'prompt' => $questionData['prompt'],
                'points' => $questionData['points'],
                'position' => $position + 1,
            ]);

            if ($questionData['type'] !== QuestionType::MultipleChoice) {
                continue;
            }

            foreach ($questionData['options'] as $optionPosition => $option) {
                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'text' => $option,
                    'is_correct' => $questionData['correct'] === $optionPosition,
                    'position' => $optionPosition + 1,
                ]);
            }
        }

        return $exam;
    }
}
