<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->whereIn('name', [
            'view academic periods',
            'manage academic periods',
            'view subjects',
            'manage subjects',
            'view teaching assignments',
            'manage teaching assignments',
            'view lesson periods',
            'manage lesson periods',
            'view lesson schedules',
            'manage lesson schedules',
        ])->delete();

        Schema::dropIfExists('lesson_schedules');
        Schema::dropIfExists('lesson_periods');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('subjects');

        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'academic_period_id')) {
            Schema::table('classes', fn ($table) => $table->dropConstrainedForeignId('academic_period_id'));
        }

        Schema::dropIfExists('academic_periods');

        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'assistant_teacher_id')) {
            Schema::table('classes', fn ($table) => $table->dropConstrainedForeignId('assistant_teacher_id'));
        }

        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'academic_year')) {
            Schema::table('classes', fn ($table) => $table->dropColumn('academic_year'));
        }

        if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'academic_year')) {
            Schema::table('school_settings', fn ($table) => $table->dropColumn('academic_year'));
        }
    }

    public function down(): void
    {
        // Penghapusan struktur dan data Kurikulum ini disengaja serta tidak dapat dipulihkan otomatis.
    }
};
