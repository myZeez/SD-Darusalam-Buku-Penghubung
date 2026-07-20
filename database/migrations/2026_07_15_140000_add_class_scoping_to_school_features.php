<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade_level')->nullable()->after('name');
            $table->unsignedSmallInteger('capacity')->default(30)->after('academic_year');
            $table->string('room')->nullable()->after('capacity');
            $table->text('description')->nullable()->after('room');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('class_id')
                ->nullable()
                ->after('id')
                ->constrained('classes')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->after('class_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('class_id');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['grade_level', 'capacity', 'room', 'description']);
        });
    }
};
