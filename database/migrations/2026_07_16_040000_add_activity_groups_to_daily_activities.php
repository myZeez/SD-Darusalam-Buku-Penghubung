<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_activities', function (Blueprint $table) {
            $table->json('activity_groups')->nullable()->after('attendance');
        });

        Schema::table('home_activities', function (Blueprint $table) {
            $table->json('activity_groups')->nullable()->after('activity_date');
        });
    }

    public function down(): void
    {
        Schema::table('school_activities', function (Blueprint $table) {
            $table->dropColumn('activity_groups');
        });

        Schema::table('home_activities', function (Blueprint $table) {
            $table->dropColumn('activity_groups');
        });
    }
};
