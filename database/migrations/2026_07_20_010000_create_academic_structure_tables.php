<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('academic_year', 9);
            $table->string('semester', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year', 'semester']);
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->foreignId('academic_period_id')
                ->nullable()
                ->after('assistant_teacher_id')
                ->constrained('academic_periods')
                ->restrictOnDelete();
        });

        $this->backfillAcademicPeriods();

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['academic_period_id', 'teacher_id', 'class_id', 'subject_id'],
                'teaching_assignment_unique',
            );
        });

        Schema::create('lesson_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sequence');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type', 20)->default('lesson');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['academic_period_id', 'sequence']);
        });

        Schema::create('lesson_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_period_id')->constrained()->restrictOnDelete();
            $table->string('day_of_week', 12);
            $table->string('room')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['teaching_assignment_id', 'lesson_period_id', 'day_of_week'],
                'lesson_schedule_assignment_unique',
            );
            $table->index(['day_of_week', 'lesson_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_schedules');
        Schema::dropIfExists('lesson_periods');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('subjects');

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('academic_period_id');
        });

        Schema::dropIfExists('academic_periods');
    }

    private function backfillAcademicPeriods(): void
    {
        $academicYears = DB::table('classes')
            ->whereNotNull('academic_year')
            ->where('academic_year', '<>', '')
            ->distinct()
            ->pluck('academic_year');

        $activeAcademicYear = Schema::hasTable('school_settings')
            ? DB::table('school_settings')->value('academic_year')
            : null;

        foreach ($academicYears as $academicYear) {
            $startYear = (int) substr((string) $academicYear, 0, 4);
            $periodId = DB::table('academic_periods')->insertGetId([
                'academic_year' => $academicYear,
                'semester' => 'odd',
                'start_date' => sprintf('%d-07-01', $startYear),
                'end_date' => sprintf('%d-12-31', $startYear),
                'is_active' => $academicYear === $activeAcademicYear,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('classes')
                ->where('academic_year', $academicYear)
                ->update(['academic_period_id' => $periodId]);
        }

        if ($academicYears->isNotEmpty() && ! DB::table('academic_periods')->where('is_active', true)->exists()) {
            DB::table('academic_periods')->whereKey(
                DB::table('academic_periods')->max('id'),
            )->update(['is_active' => true]);
        }
    }
};
