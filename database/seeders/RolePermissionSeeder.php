<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view users',
            'manage users',
            'view teachers',
            'manage teachers',
            'view parents',
            'manage parents',
            'view students',
            'manage students',
            'view classes',
            'manage classes',
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
            'view schedules',
            'manage schedules',
            'view reports',
            'view school activities',
            'manage school activities',
            'view home activities',
            'manage home activities',
            'view comments',
            'manage comments',
            'view notifications',
            'manage notifications',
            'view school settings',
            'manage school settings',
            'view arrivals',
            'manage arrivals',
            'view attendances',
            'manage attendances',
            'view parent submissions',
            'manage parent submissions',
            'view extracurriculars',
            'manage extracurriculars',
            'view audit logs',
            'export reports',
            'view password reset requests',
            'manage password reset requests',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(array_values(array_diff($permissions, [
            'manage parent submissions',
        ])));
        Role::findOrCreate('loket')->syncPermissions([
            'view arrivals',
            'manage arrivals',
        ]);
        Role::findOrCreate('guru')->syncPermissions([
            'view teachers',
            'view students',
            'view classes',
            'view teaching assignments',
            'view lesson schedules',
            'view schedules',
            'manage schedules',
            'view school activities',
            'manage school activities',
            'view home activities',
            'view comments',
            'manage comments',
            'view notifications',
            'manage notifications',
            'view attendances',
            'manage attendances',
            'view parent submissions',
            'manage parent submissions',
        ]);
        Role::findOrCreate('orang_tua')->syncPermissions([
            'view students',
            'view reports',
            'export reports',
            'view lesson schedules',
            'view schedules',
            'view school activities',
            'view home activities',
            'manage home activities',
            'view comments',
            'manage comments',
            'view notifications',
            'view attendances',
            'view parent submissions',
            'manage parent submissions',
            'view extracurriculars',
        ]);
        Role::findOrCreate('siswa')->syncPermissions([
            'view students',
            'view extracurriculars',
        ]);
    }
}
