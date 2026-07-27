<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_activities', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('photo');
            $table->foreignId('submitted_by')
                ->nullable()
                ->after('submitted_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('home_activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('submitted_at');
        });
    }
};
