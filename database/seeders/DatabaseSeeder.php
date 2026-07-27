<?php

namespace Database\Seeders;

use App\Models\ParentProfile;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = Role::findByName('admin');
        $loket = Role::findByName('loket');
        $guru = Role::findByName('guru');
        $orangTua = Role::findByName('orang_tua');
        $siswa = Role::findByName('siswa');

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sddarusalam.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '080000000001',
                'status' => 'active',
            ],
        );
        $adminUser->update([
            'name' => 'Administrator Sekolah',
            'job_title' => 'Administrator Sekolah',
        ]);
        $adminUser->syncRoles([$admin]);

        SchoolSetting::current();

        $principalUser = User::firstOrCreate(
            ['email' => 'kepala.sekolah@sddarusalam.test'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('password'),
                'phone' => '080000000005',
                'status' => 'active',
            ],
        );
        $principalUser->update(['job_title' => 'Kepala Sekolah']);
        $principalUser->syncRoles([$admin]);

        $gateUser = User::firstOrCreate(
            ['email' => 'loket@sddarusalam.test'],
            [
                'name' => 'Petugas Loket',
                'password' => Hash::make('password'),
                'phone' => '080000000006',
                'status' => 'active',
            ],
        );
        $gateUser->syncRoles([$loket]);

        $teacherUser = User::firstOrCreate(
            ['email' => 'guru@sddarusalam.test'],
            [
                'name' => 'Guru Kelas',
                'password' => Hash::make('password'),
                'phone' => '080000000002',
                'status' => 'active',
            ],
        );
        $teacherUser->syncRoles([$guru]);

        $parentUser = User::firstOrCreate(
            ['email' => 'ortu@sddarusalam.test'],
            [
                'name' => 'Orang Tua Siswa',
                'password' => Hash::make('password'),
                'phone' => '080000000003',
                'status' => 'active',
            ],
        );
        $parentUser->syncRoles([$orangTua]);

        $studentUser = User::firstOrCreate(
            ['email' => 'siswa@sddarusalam.test'],
            [
                'name' => 'Ahmad Darusalam',
                'password' => Hash::make('password'),
                'phone' => '080000000004',
                'status' => 'active',
            ],
        );
        $studentUser->syncRoles([$siswa]);

        $teacher = Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'nip' => '197001012000011001',
                'gender' => 'female',
                'address' => 'SD Darusalam',
            ],
        );

        $parent = ParentProfile::firstOrCreate(
            ['user_id' => $parentUser->id],
            [
                'father_name' => 'Bapak Siswa',
                'mother_name' => 'Ibu Siswa',
                'phone' => '080000000003',
                'address' => 'Sekitar SD Darusalam',
            ],
        );

        $class = SchoolClass::firstOrCreate(
            ['name' => 'Kelas 1A'],
            [
                'teacher_id' => $teacher->id,
                'grade_level' => 1,
                'capacity' => 30,
                'room' => 'Ruang 1A',
            ],
        );

        $class->update([
            'teacher_id' => $teacher->id,
            'grade_level' => $class->grade_level ?? 1,
            'capacity' => $class->capacity ?: 30,
            'room' => $class->room ?: 'Ruang 1A',
        ]);

        Student::updateOrCreate(
            ['nis' => 'SDD-001'],
            [
                'user_id' => $studentUser->id,
                'class_id' => $class->id,
                'parent_id' => $parent->id,
                'name' => 'Ahmad Darusalam',
                'gender' => 'male',
                'birth_date' => '2019-05-12',
                'status' => 'active',
            ],
        );

        Schedule::firstOrCreate([
            'title' => 'Pertemuan Orang Tua',
            'activity_date' => now()->addWeek()->toDateString(),
        ], [
            'description' => 'Koordinasi awal tahun ajaran dan pengenalan buku penghubung digital.',
            'location' => 'Aula SD Darusalam',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'equipment' => 'Buku catatan dan alat tulis',
        ]);
    }
}
