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
        Schema::create('home_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->date('activity_date')->index();
            $table->boolean('worship')->default(false);
            $table->boolean('study')->default(false);
            $table->boolean('homework')->default(false);
            $table->boolean('sleep')->default(false);
            $table->boolean('meal')->default(false);
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
        Schema::dropIfExists('home_activities');
    }
};
