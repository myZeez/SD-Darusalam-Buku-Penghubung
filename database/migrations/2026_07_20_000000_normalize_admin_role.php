<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('job_title')->nullable()->after('phone');
        });

        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        DB::transaction(function (): void {
            $adminRoleId = DB::table('roles')
                ->where('name', 'admin')
                ->where('guard_name', 'web')
                ->value('id');

            if (! $adminRoleId) {
                $adminRoleId = DB::table('roles')->insertGetId([
                    'name' => 'admin',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $legacyRoles = DB::table('roles')
                ->whereIn('name', ['super_admin', 'kepala_sekolah'])
                ->where('guard_name', 'web')
                ->get(['id', 'name']);

            $legacyRoleIds = $legacyRoles->pluck('id');

            if ($legacyRoleIds->isEmpty()) {
                return;
            }

            foreach ($legacyRoles as $legacyRole) {
                $userIds = DB::table('model_has_roles')
                    ->where('role_id', $legacyRole->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->pluck('model_id');

                DB::table('users')
                    ->whereIn('id', $userIds)
                    ->whereNull('job_title')
                    ->update([
                        'job_title' => $legacyRole->name === 'kepala_sekolah'
                            ? 'Kepala Sekolah'
                            : 'Administrator Sekolah',
                    ]);

                if ($legacyRole->name === 'super_admin') {
                    DB::table('users')
                        ->whereIn('id', $userIds)
                        ->where('name', 'Super Admin')
                        ->update(['name' => 'Administrator Sekolah']);
                }
            }

            DB::table('model_has_roles')
                ->whereIn('role_id', $legacyRoleIds)
                ->get()
                ->each(function (object $assignment) use ($adminRoleId): void {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $adminRoleId,
                        'model_type' => $assignment->model_type,
                        'model_id' => $assignment->model_id,
                    ]);
                });

            DB::table('model_has_roles')->whereIn('role_id', $legacyRoleIds)->delete();

            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->whereIn('role_id', $legacyRoleIds)->delete();
            }

            DB::table('roles')->whereIn('id', $legacyRoleIds)->delete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('job_title');
        });
    }
};
