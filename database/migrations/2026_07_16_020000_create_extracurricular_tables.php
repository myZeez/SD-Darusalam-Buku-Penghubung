<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('extracurricular_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('joined_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id'], 'extracurricular_student_unique');
        });

        Schema::create('extracurricular_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('session_date');
            $table->string('title');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('material')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();
            $table->string('coach_attendance_status')->default('present');
            $table->text('coach_notes')->nullable();
            $table->timestamps();

            $table->index(['extracurricular_id', 'session_date']);
        });

        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['extracurricular_session_id', 'student_id'], 'extracurricular_attendance_unique');
        });

        Schema::create('extracurricular_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->decimal('score', 5, 2);
            $table->date('assessed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['extracurricular_id', 'student_id', 'assessed_at'], 'extracurricular_score_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_scores');
        Schema::dropIfExists('extracurricular_attendances');
        Schema::dropIfExists('extracurricular_sessions');
        Schema::dropIfExists('extracurricular_enrollments');
        Schema::dropIfExists('extracurriculars');
    }
};
