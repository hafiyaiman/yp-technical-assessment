<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lecturer_id', 'school_class_id', 'subject_id'], 'teaching_assignments_unique');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->foreignId('teaching_assignment_id')
                ->nullable()
                ->after('lecturer_id')
                ->constrained('teaching_assignments')
                ->nullOnDelete();
        });

        DB::table('exams')
            ->select(['id', 'lecturer_id', 'school_class_id', 'subject_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $exam): void {
                $assignment = DB::table('teaching_assignments')
                    ->where('lecturer_id', $exam->lecturer_id)
                    ->where('school_class_id', $exam->school_class_id)
                    ->where('subject_id', $exam->subject_id)
                    ->first();

                $assignmentId = $assignment?->id ?? DB::table('teaching_assignments')->insertGetId([
                    'lecturer_id' => $exam->lecturer_id,
                    'school_class_id' => $exam->school_class_id,
                    'subject_id' => $exam->subject_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('exams')
                    ->where('id', $exam->id)
                    ->update(['teaching_assignment_id' => $assignmentId]);
            });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('teaching_assignment_id');
        });

        Schema::dropIfExists('teaching_assignments');
    }
};
