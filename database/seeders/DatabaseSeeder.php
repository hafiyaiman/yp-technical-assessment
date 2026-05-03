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

        // ─── Exams ────────────────────────────────────────────────────────────

        $this->exam($assignments['CLASS-4A:MATH'], [
            'title' => 'Mathematics Mid-Year Examination',
            'instructions' => 'This exam consists of 40 multiple-choice questions and 2 open-text questions. Each multiple-choice question carries 1 mark. Read each question carefully before answering. You have 60 minutes.',
            'duration_minutes' => 60,
            'status' => ExamStatus::Published,
            'questions' => [
                // ── Multiple Choice (40 questions) ──────────────────────────
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 8 × 7?', 'points' => 1, 'options' => ['54', '56', '64', '48'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 144 ÷ 12?', 'points' => 1, 'options' => ['10', '11', '12', '13'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the value of 5²?', 'points' => 1, 'options' => ['10', '15', '20', '25'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the square root of 81?', 'points' => 1, 'options' => ['7', '8', '9', '10'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 15% of 200?', 'points' => 1, 'options' => ['20', '25', '30', '35'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which of the following is a prime number?', 'points' => 1, 'options' => ['9', '15', '17', '21'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 3/4 expressed as a decimal?', 'points' => 1, 'options' => ['0.34', '0.43', '0.70', '0.75'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the LCM of 4 and 6?', 'points' => 1, 'options' => ['8', '10', '12', '24'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the HCF of 24 and 36?', 'points' => 1, 'options' => ['4', '6', '8', '12'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Simplify: 18/24', 'points' => 1, 'options' => ['2/3', '3/4', '4/5', '5/6'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 2.5 × 4?', 'points' => 1, 'options' => ['8', '9', '10', '11'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 1000 − 378?', 'points' => 1, 'options' => ['512', '622', '632', '722'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'A triangle has angles 60° and 80°. What is the third angle?', 'points' => 1, 'options' => ['30°', '40°', '50°', '60°'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the perimeter of a square with side 7 cm?', 'points' => 1, 'options' => ['14 cm', '21 cm', '28 cm', '49 cm'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the area of a rectangle 9 cm long and 5 cm wide?', 'points' => 1, 'options' => ['14 cm²', '28 cm²', '40 cm²', '45 cm²'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'How many millimetres are in 3.5 cm?', 'points' => 1, 'options' => ['3.5 mm', '35 mm', '350 mm', '3500 mm'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 40% of 150?', 'points' => 1, 'options' => ['40', '50', '60', '70'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Express 0.6 as a fraction in its simplest form.', 'points' => 1, 'options' => ['6/10', '3/4', '3/5', '2/3'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the next term in the sequence: 2, 5, 10, 17, ___?', 'points' => 1, 'options' => ['24', '25', '26', '28'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'If x + 9 = 21, what is x?', 'points' => 1, 'options' => ['10', '11', '12', '13'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Solve: 3x = 36', 'points' => 1, 'options' => ['9', '10', '11', '12'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the value of 4³?', 'points' => 1, 'options' => ['12', '16', '48', '64'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which shape has exactly 4 lines of symmetry?', 'points' => 1, 'options' => ['Rectangle', 'Parallelogram', 'Square', 'Trapezium'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the volume of a cube with side 3 cm?', 'points' => 1, 'options' => ['9 cm³', '18 cm³', '27 cm³', '81 cm³'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'A bag has 3 red, 4 blue, and 5 green balls. What is the probability of picking a blue ball?', 'points' => 1, 'options' => ['1/4', '1/3', '4/12', '1/2'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 7/8 − 1/4?', 'points' => 1, 'options' => ['5/8', '6/8', '1/2', '3/4'], 'correct' => 0],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 2/3 + 1/6?', 'points' => 1, 'options' => ['3/9', '5/6', '1/2', '4/6'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'A train travels 240 km in 3 hours. What is its average speed?', 'points' => 1, 'options' => ['60 km/h', '70 km/h', '80 km/h', '90 km/h'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the mean of 4, 7, 9, 10, 15?', 'points' => 1, 'options' => ['7', '8', '9', '10'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the median of 3, 7, 8, 12, 15?', 'points' => 1, 'options' => ['7', '8', '9', '12'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the mode of 2, 4, 4, 5, 6, 6, 6, 7?', 'points' => 1, 'options' => ['4', '5', '6', '7'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Convert 3/5 to a percentage.', 'points' => 1, 'options' => ['35%', '53%', '60%', '65%'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the circumference of a circle with radius 7 cm? (Use π = 22/7)', 'points' => 1, 'options' => ['22 cm', '44 cm', '154 cm', '308 cm'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which of these numbers is divisible by both 3 and 4?', 'points' => 1, 'options' => ['10', '14', '24', '26'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 5! (5 factorial)?', 'points' => 1, 'options' => ['20', '60', '100', '120'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 0.25 × 0.4?', 'points' => 1, 'options' => ['0.01', '0.1', '0.1', '1.0'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'If a shirt costs RM80 and is discounted by 25%, what is the sale price?', 'points' => 1, 'options' => ['RM55', 'RM60', 'RM65', 'RM70'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the sum of angles in a quadrilateral?', 'points' => 1, 'options' => ['180°', '270°', '360°', '540°'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'A rectangle has a perimeter of 36 cm and a width of 6 cm. What is its length?', 'points' => 1, 'options' => ['10 cm', '12 cm', '14 cm', '16 cm'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is 1.2 × 10³?', 'points' => 1, 'options' => ['12', '120', '1200', '12000'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which of the following is NOT a factor of 48?', 'points' => 1, 'options' => ['6', '8', '9', '12'], 'correct' => 2],

                // ── Open Text (2 questions) ──────────────────────────────────
                ['type' => QuestionType::OpenText, 'prompt' => 'A rectangular swimming pool is 25 m long and 10 m wide. The depth is 2 m throughout. Calculate the volume of water needed to fill the pool completely. Show your working.', 'points' => 5],
                ['type' => QuestionType::OpenText, 'prompt' => 'Explain the difference between mean, median, and mode. Give an example of a dataset where the mean would not be the best measure of average, and justify your answer.', 'points' => 5],
            ],
        ]);

        $this->exam($assignments['CLASS-4A:SCI'], [
            'title' => 'Science Mid-Year Examination',
            'instructions' => 'This exam has 40 multiple-choice questions worth 1 mark each and 2 open-text questions. You have 60 minutes.',
            'duration_minutes' => 60,
            'status' => ExamStatus::Published,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which force pulls objects toward Earth?', 'points' => 1, 'options' => ['Friction', 'Gravity', 'Magnetism', 'Tension'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the chemical symbol for water?', 'points' => 1, 'options' => ['WA', 'HO', 'H₂O', 'H₂O₂'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What type of energy does a moving object have?', 'points' => 1, 'options' => ['Potential', 'Thermal', 'Kinetic', 'Chemical'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which planet is closest to the Sun?', 'points' => 1, 'options' => ['Venus', 'Earth', 'Mars', 'Mercury'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the process by which plants make food?', 'points' => 1, 'options' => ['Respiration', 'Photosynthesis', 'Transpiration', 'Digestion'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which part of the cell controls its activities?', 'points' => 1, 'options' => ['Cell wall', 'Cytoplasm', 'Nucleus', 'Vacuole'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the unit of electrical resistance?', 'points' => 1, 'options' => ['Volt', 'Ampere', 'Watt', 'Ohm'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which gas do plants absorb during photosynthesis?', 'points' => 1, 'options' => ['Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What state of matter has a fixed volume but no fixed shape?', 'points' => 1, 'options' => ['Solid', 'Liquid', 'Gas', 'Plasma'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the speed of light in a vacuum?', 'points' => 1, 'options' => ['300 km/s', '3000 km/s', '300,000 km/s', '3,000,000 km/s'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which organ pumps blood around the body?', 'points' => 1, 'options' => ['Liver', 'Kidney', 'Lung', 'Heart'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the powerhouse of the cell?', 'points' => 1, 'options' => ['Ribosome', 'Mitochondria', 'Nucleus', 'Golgi body'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which type of rock is formed from cooled lava?', 'points' => 1, 'options' => ['Sedimentary', 'Metamorphic', 'Igneous', 'Limestone'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the boiling point of water at sea level?', 'points' => 1, 'options' => ['50°C', '80°C', '100°C', '120°C'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which vitamin is produced when the skin is exposed to sunlight?', 'points' => 1, 'options' => ['Vitamin A', 'Vitamin B', 'Vitamin C', 'Vitamin D'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the name of the force that opposes motion?', 'points' => 1, 'options' => ['Gravity', 'Tension', 'Friction', 'Normal force'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'How many bones are in the adult human body?', 'points' => 1, 'options' => ['186', '196', '206', '216'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What type of circuit has only one path for current to flow?', 'points' => 1, 'options' => ['Parallel', 'Series', 'Complex', 'Open'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the pH of pure water?', 'points' => 1, 'options' => ['5', '6', '7', '8'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which layer of the Earth is the thickest?', 'points' => 1, 'options' => ['Crust', 'Mantle', 'Outer core', 'Inner core'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the main gas found in the Earth\'s atmosphere?', 'points' => 1, 'options' => ['Oxygen', 'Carbon Dioxide', 'Nitrogen', 'Hydrogen'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does DNA stand for?', 'points' => 1, 'options' => ['Deoxyribose Nucleic Acid', 'Dynamic Nuclear Atom', 'Deoxyribonucleic Acid', 'Dinitrogen Acid'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which planet has the most known moons?', 'points' => 1, 'options' => ['Jupiter', 'Saturn', 'Uranus', 'Neptune'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the term for animals that eat only plants?', 'points' => 1, 'options' => ['Carnivore', 'Omnivore', 'Herbivore', 'Decomposer'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which element has the atomic number 1?', 'points' => 1, 'options' => ['Helium', 'Oxygen', 'Carbon', 'Hydrogen'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the bending of light as it passes from one medium to another called?', 'points' => 1, 'options' => ['Reflection', 'Refraction', 'Diffraction', 'Absorption'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which blood type is the universal donor?', 'points' => 1, 'options' => ['A', 'B', 'AB', 'O'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What force keeps planets in orbit around the Sun?', 'points' => 1, 'options' => ['Magnetic force', 'Nuclear force', 'Gravitational force', 'Electric force'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the process of water turning into vapour called?', 'points' => 1, 'options' => ['Condensation', 'Precipitation', 'Evaporation', 'Sublimation'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which part of a plant absorbs water from the soil?', 'points' => 1, 'options' => ['Leaves', 'Stem', 'Roots', 'Flowers'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the chemical formula for table salt?', 'points' => 1, 'options' => ['KCl', 'NaCl', 'MgCl₂', 'CaCl₂'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which type of energy is stored in food?', 'points' => 1, 'options' => ['Kinetic', 'Thermal', 'Nuclear', 'Chemical'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the term for the change of a caterpillar into a butterfly?', 'points' => 1, 'options' => ['Adaptation', 'Metamorphosis', 'Evolution', 'Germination'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the unit for measuring force?', 'points' => 1, 'options' => ['Watt', 'Joule', 'Newton', 'Pascal'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which instrument is used to measure temperature?', 'points' => 1, 'options' => ['Barometer', 'Thermometer', 'Hydrometer', 'Ammeter'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the smallest unit of matter?', 'points' => 1, 'options' => ['Cell', 'Molecule', 'Atom', 'Electron'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What do you call organisms that break down dead matter?', 'points' => 1, 'options' => ['Producers', 'Consumers', 'Decomposers', 'Predators'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which gas makes up about 21% of the Earth\'s atmosphere?', 'points' => 1, 'options' => ['Nitrogen', 'Oxygen', 'Carbon dioxide', 'Argon'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is a renewable energy source?', 'points' => 1, 'options' => ['Coal', 'Petroleum', 'Natural gas', 'Solar power'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the name of the process where a solid turns directly into a gas?', 'points' => 1, 'options' => ['Evaporation', 'Condensation', 'Sublimation', 'Freezing'], 'correct' => 2],

                ['type' => QuestionType::OpenText, 'prompt' => 'Explain how photosynthesis works. In your answer, include the raw materials needed, the products formed, and the role of sunlight and chlorophyll.', 'points' => 5],
                ['type' => QuestionType::OpenText, 'prompt' => 'Describe the water cycle. Include the processes of evaporation, condensation, and precipitation in your explanation.', 'points' => 5],
            ],
        ]);

        $this->exam($assignments['CLASS-4A:ENG'], [
            'title' => 'English Language Mid-Year Examination',
            'instructions' => 'Answer all 40 multiple-choice questions and 2 open-text questions. Each multiple-choice question carries 1 mark. You have 60 minutes.',
            'duration_minutes' => 60,
            'status' => ExamStatus::Published,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence is grammatically correct?', 'points' => 1, 'options' => ['She go to school.', 'She goes to school.', 'She going to school.', 'She goed to school.'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is a synonym for "happy"?', 'points' => 1, 'options' => ['Sad', 'Angry', 'Joyful', 'Tired'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the plural of "child"?', 'points' => 1, 'options' => ['Childs', 'Childes', 'Children', 'Childrens'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is an antonym of "ancient"?', 'points' => 1, 'options' => ['Old', 'Modern', 'Historic', 'Classic'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Choose the correct article: "___ umbrella is in the car."', 'points' => 1, 'options' => ['A', 'An', 'The', 'No article needed'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence is in the passive voice?', 'points' => 1, 'options' => ['The dog chased the cat.', 'The cat was chased by the dog.', 'The dog is chasing the cat.', 'The cat ran away.'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does the prefix "un-" mean in the word "unhappy"?', 'points' => 1, 'options' => ['Very', 'Again', 'Not', 'Before'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which punctuation ends an exclamatory sentence?', 'points' => 1, 'options' => ['.', ',', '?', '!'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is a conjunction in: "I was tired, but I finished the work."?', 'points' => 1, 'options' => ['tired', 'but', 'finished', 'work'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the correct past tense of "run"?', 'points' => 1, 'options' => ['Runned', 'Runs', 'Ran', 'Running'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is a proper noun?', 'points' => 1, 'options' => ['city', 'river', 'Malaysia', 'mountain'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Choose the correctly spelled word.', 'points' => 1, 'options' => ['Recieve', 'Receive', 'Recive', 'Receeve'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is a metaphor?', 'points' => 1, 'options' => ['A direct comparison using "like" or "as"', 'An indirect comparison without using "like" or "as"', 'An exaggerated statement', 'A question that does not require an answer'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence contains a simile?', 'points' => 1, 'options' => ['The wind whispered.', 'He is a lion.', 'She runs like the wind.', 'The stars danced.'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => '"The stars danced in the sky." This is an example of:', 'points' => 1, 'options' => ['Simile', 'Metaphor', 'Personification', 'Alliteration'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does "benevolent" mean?', 'points' => 1, 'options' => ['Cruel', 'Kind and generous', 'Angry', 'Fearful'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word correctly completes the sentence: "Neither the students nor the teacher ___ ready."?', 'points' => 1, 'options' => ['were', 'are', 'was', 'be'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the subject of the sentence: "The tall woman carried a red bag."?', 'points' => 1, 'options' => ['tall', 'woman', 'carried', 'bag'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is an adverb in: "She spoke softly."?', 'points' => 1, 'options' => ['She', 'spoke', 'softly', 'None'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does the idiom "break the ice" mean?', 'points' => 1, 'options' => ['To literally break frozen water', 'To start a conversation in a social setting', 'To end an argument', 'To cause trouble'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence uses a semicolon correctly?', 'points' => 1, 'options' => ['I love reading; books.', 'She was tired; she went to sleep.', 'He; ran home.', 'The cat; sat down.'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the tone of a formal letter?', 'points' => 1, 'options' => ['Casual and friendly', 'Humorous', 'Polite and professional', 'Aggressive'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Choose the sentence with correct subject-verb agreement.', 'points' => 1, 'options' => ['The team are playing well.', 'The team is playing well.', 'The team were playing well.', 'The team be playing well.'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is alliteration?', 'points' => 1, 'options' => ['Repetition of vowel sounds', 'Repetition of the same consonant sound at the start of words', 'A comparison using "like"', 'An exaggeration'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence is in the future tense?', 'points' => 1, 'options' => ['She walked to school.', 'She walks to school.', 'She will walk to school.', 'She is walking to school.'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the function of a topic sentence in a paragraph?', 'points' => 1, 'options' => ['To conclude the paragraph', 'To introduce the main idea', 'To provide evidence', 'To ask a question'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is a preposition in: "The book is on the table."?', 'points' => 1, 'options' => ['book', 'is', 'on', 'table'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => '"Peter Piper picked a peck of pickled peppers." This is an example of:', 'points' => 1, 'options' => ['Metaphor', 'Simile', 'Alliteration', 'Personification'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does a bibliography list?', 'points' => 1, 'options' => ['The main ideas of an essay', 'Sources used in a piece of writing', 'The contents of a book', 'Important vocabulary'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the correct comparative form of "good"?', 'points' => 1, 'options' => ['Gooder', 'More good', 'Better', 'Best'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which word is a homophone of "flour"?', 'points' => 1, 'options' => ['Flow', 'Floor', 'Flower', 'Flavor'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'In a narrative essay, what is the climax?', 'points' => 1, 'options' => ['The introduction of characters', 'The turning point or most intense moment', 'The resolution', 'The setting description'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence contains a relative clause?', 'points' => 1, 'options' => ['She sang beautifully.', 'The boy who won the race is my brother.', 'He ran quickly.', 'They played football.'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What does "ambiguous" mean?', 'points' => 1, 'options' => ['Clear and definite', 'Open to more than one interpretation', 'Completely wrong', 'Very simple'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which is the correct possessive form?', 'points' => 1, 'options' => ['The dogs bone', 'The dog\'s bone', 'The dogs\' bone', 'The dogs bones'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What literary device is used in "The thunder roared angrily"?', 'points' => 1, 'options' => ['Simile', 'Metaphor', 'Personification', 'Hyperbole'], 'correct' => 2],
                ['type' => QuestionType::MultipleChoice, 'prompt' => '"I\'ve told you a million times!" is an example of:', 'points' => 1, 'options' => ['Simile', 'Metaphor', 'Alliteration', 'Hyperbole'], 'correct' => 3],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is the purpose of a concluding paragraph?', 'points' => 1, 'options' => ['To introduce new arguments', 'To restate the thesis and summarise key points', 'To ask the reader questions', 'To list evidence'], 'correct' => 1],
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which is the correct spelling?', 'points' => 1, 'options' => ['Accomodate', 'Accommodate', 'Acommodate', 'Acomodate'], 'correct' => 1],

                ['type' => QuestionType::OpenText, 'prompt' => 'Write a well-structured paragraph (8–10 sentences) describing a memorable event in your life. Use at least two literary devices and underline them.', 'points' => 5],
                ['type' => QuestionType::OpenText, 'prompt' => 'Read the following statement: "Social media does more harm than good for teenagers." Do you agree or disagree? Write a short essay (3 paragraphs) giving reasons to support your view.', 'points' => 5],
            ],
        ]);

        // ── Remaining original exams ──────────────────────────────────────────

        $this->exam($assignments['CLASS-1A:ENG'], [
            'title' => 'English Grammar Sprint',
            'instructions' => 'Choose the best answer, then write one short explanation.',
            'duration_minutes' => 20,
            'status' => ExamStatus::Published,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which sentence uses the correct tense?', 'points' => 2, 'options' => ['She go to school.', 'She goes to school.', 'She going to school.'], 'correct' => 1],
                ['type' => QuestionType::OpenText, 'prompt' => 'Write a sentence using the word "although".', 'points' => 4],
            ],
        ]);

        $this->exam($assignments['CLASS-2B:ICT'], [
            'title' => 'ICT Practical Concepts',
            'instructions' => 'This draft exam is available for lecturer editing.',
            'duration_minutes' => 25,
            'status' => ExamStatus::Draft,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which device is used for long-term data storage?', 'points' => 2, 'options' => ['RAM', 'SSD', 'CPU'], 'correct' => 1],
                ['type' => QuestionType::OpenText, 'prompt' => 'Describe one safe password practice.', 'points' => 3],
            ],
        ]);

        $this->exam($assignments['CLASS-3C:SCI'], [
            'title' => 'Science Forces Review',
            'instructions' => 'Use examples from daily life where helpful.',
            'duration_minutes' => 30,
            'status' => ExamStatus::Published,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which force pulls objects toward Earth?', 'points' => 2, 'options' => ['Friction', 'Gravity', 'Magnetism'], 'correct' => 1],
                ['type' => QuestionType::OpenText, 'prompt' => 'Explain how friction can be useful.', 'points' => 5],
            ],
        ]);

        $this->exam($assignments['CLASS-3C:HIST'], [
            'title' => 'History Source Analysis',
            'instructions' => 'Read each source carefully before answering.',
            'duration_minutes' => 35,
            'status' => ExamStatus::Closed,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'What is a primary source?', 'points' => 2, 'options' => ['A first-hand record', 'A modern textbook summary', 'A fictional story'], 'correct' => 0],
                ['type' => QuestionType::OpenText, 'prompt' => 'Why should historians compare multiple sources?', 'points' => 5],
            ],
        ]);

        $this->exam($assignments['CLASS-5S:SCI'], [
            'title' => 'Science Extended Response',
            'instructions' => 'Open-text answers will be marked by your lecturer.',
            'duration_minutes' => 40,
            'status' => ExamStatus::Published,
            'questions' => [
                ['type' => QuestionType::MultipleChoice, 'prompt' => 'Which process do plants use to make food?', 'points' => 2, 'options' => ['Photosynthesis', 'Condensation', 'Evaporation'], 'correct' => 0],
                ['type' => QuestionType::OpenText, 'prompt' => 'Explain why sunlight is important to plants.', 'points' => 6],
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
