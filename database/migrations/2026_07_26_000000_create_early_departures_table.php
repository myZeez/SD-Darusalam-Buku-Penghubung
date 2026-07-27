<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_submissions', function (Blueprint $table): void {
            $table->time('early_leave_time')->nullable()->after('end_date');
        });

        Schema::create('early_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('parent_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('departure_date');
            $table->time('departure_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'departure_date']);
            $table->index(['class_id', 'departure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_departures');

        Schema::table('parent_submissions', function (Blueprint $table): void {
            $table->dropColumn('early_leave_time');
        });
    }
};
