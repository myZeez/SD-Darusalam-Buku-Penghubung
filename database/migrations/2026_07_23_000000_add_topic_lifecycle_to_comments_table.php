<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('category')->default('other')->after('user_id');
            $table->string('topic')->nullable()->after('category');
            $table->string('status')->default('open')->after('comment');
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'category']);
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn(['category', 'topic', 'status', 'closed_at']);
        });
    }
};
