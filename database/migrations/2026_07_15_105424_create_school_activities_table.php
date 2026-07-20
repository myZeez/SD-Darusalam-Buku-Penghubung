<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->date('activity_date')->index();
            $table->string('attendance')->default('present');
            $table->text('morning_activity')->nullable();
            $table->text('learning_activity')->nullable();
            $table->text('religious_activity')->nullable();
            $table->text('character_building')->nullable();
            $table->text('motoric_activity')->nullable();
            $table->text('break_activity')->nullable();
            $table->text('cleanliness')->nullable();
            $table->text('independence')->nullable();
            $table->text('note')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_activities');
    }
};
