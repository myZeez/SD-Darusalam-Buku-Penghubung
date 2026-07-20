<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\LessonPeriod;
use App\Models\LessonSchedule;
use App\Models\ParentProfile;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
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

        $schoolSetting = SchoolSetting::current();
        $startYear = (int) substr($schoolSetting->academic_year, 0, 4);
        $academicPeriod = AcademicPeriod::firstOrCreate(
            [
                'academic_year' => $schoolSetting->academic_year,
                'semester' => 'odd',
            ],
            [
                'start_date' => "{$startYear}-07-01",
                'end_date' => "{$startYear}-12-31",
                'is_active' => true,
            ],
        );

        if (! $academicPeriod->is_active) {
            $academicPeriod->update(['is_active' => true]);
        }

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
            ['name' => 'Kelas 1A', 'academic_year' => '2026/2027'],
            [
                'teacher_id' => $teacher->id,
                'academic_period_id' => $academicPeriod->id,
                'grade_level' => 1,
                'capacity' => 30,
                'room' => 'Ruang 1A',
            ],
        );

        $class->update([
            'teacher_id' => $teacher->id,
            'academic_period_id' => $academicPeriod->id,
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

        $subject = Subject::firstOrCreate(
            ['code' => 'MTK'],
            [
                'name' => 'Matematika',
                'description' => 'Mata pelajaran Matematika tingkat sekolah dasar.',
                'is_active' => true,
            ],
        );
        $assignment = TeachingAssignment::firstOrCreate([
            'academic_period_id' => $academicPeriod->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ], [
            'is_active' => true,
        ]);
        $lessonPeriod = LessonPeriod::firstOrCreate([
            'academic_period_id' => $academicPeriod->id,
            'sequence' => 1,
        ], [
            'name' => 'Jam Pelajaran 1',
            'start_time' => '07:00:00',
            'end_time' => '07:35:00',
            'type' => 'lesson',
            'is_active' => true,
        ]);
        LessonSchedule::firstOrCreate([
            'teaching_assignment_id' => $assignment->id,
            'lesson_period_id' => $lessonPeriod->id,
            'day_of_week' => 'monday',
        ], [
            'room' => $class->room,
            'is_active' => true,
        ]);

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
