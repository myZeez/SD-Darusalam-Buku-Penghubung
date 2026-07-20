<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('response', 32);
            $table->date('proposed_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'user_id']);
            $table->index(['user_id', 'response']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_responses');
    }
};
