<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSecurity;
use App\Filament\Pages\ActivityReports;
use App\Filament\Pages\TeacherAttendance;
use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePageAccessAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, class-string> */
    private array $resources = [
        'users' => UserResource::class,
        'teachers' => TeacherResource::class,
        'parents' => ParentProfileResource::class,
        'students' => StudentResource::class,
        'classes' => SchoolClassResource::class,
        'academic_periods' => AcademicPeriodResource::class,
        'subjects' => SubjectResource::class,
        'teaching_assignments' => TeachingAssignmentResource::class,
        'lesson_periods' => LessonPeriodResource::class,
        'lesson_schedules' => LessonScheduleResource::class,
        'school_settings' => SchoolSettingResource::class,
        'school_activities' => SchoolActivityResource::class,
        'home_activities' => HomeActivityResource::class,
        'comments' => ActivityCommentResource::class,
        'schedules' => ScheduleResource::class,
        'notifications' => UserNotificationResource::class,
        'arrivals' => StudentArrivalResource::class,
        'attendances' => AttendanceRecordResource::class,
        'parent_submissions' => ParentSubmissionResource::class,
        'extracurriculars' => ExtracurricularResource::class,
        'password_resets' => PasswordResetRequestResource::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_every_resource_has_the_expected_view_boundary_for_each_role(): void
    {
        $expected = [
            'admin' => ['users', 'teachers', 'parents', 'students', 'classes', 'academic_periods', 'subjects', 'teaching_assignments', 'lesson_periods', 'lesson_schedules', 'school_settings', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'arrivals', 'attendances', 'parent_submissions', 'extracurriculars', 'password_resets'],
            'loket' => ['arrivals'],
            'guru' => ['teachers', 'students', 'classes', 'teaching_assignments', 'lesson_schedules', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'parent_submissions'],
            'orang_tua' => ['parents', 'students', 'lesson_schedules', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'attendances', 'parent_submissions', 'extracurriculars'],
            'siswa' => ['students', 'extracurriculars'],
        ];

        foreach ($expected as $role => $allowed) {
            $this->actingAs(User::role($role)->firstOrFail());

            foreach ($this->resources as $key => $resource) {
                $this->assertSame(
                    in_array($key, $allowed, true),
                    $resource::canViewAny(),
                    "Akses lihat {$key} untuk role {$role} tidak sesuai.",
                );
            }
        }
    }

    public function test_every_create_button_follows_the_role_task_boundary(): void
    {
        $expected = [
            'admin' => ['users', 'teachers', 'parents', 'students', 'classes', 'academic_periods', 'subjects', 'teaching_assignments', 'lesson_periods', 'lesson_schedules', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'arrivals', 'attendances', 'extracurriculars'],
            'loket' => ['arrivals'],
            'guru' => ['students', 'school_activities', 'comments', 'schedules', 'notifications'],
            'orang_tua' => ['home_activities', 'comments', 'parent_submissions'],
            'siswa' => [],
        ];

        foreach ($expected as $role => $allowed) {
            $this->actingAs(User::role($role)->firstOrFail());

            foreach ($this->resources as $key => $resource) {
                $this->assertSame(
                    in_array($key, $allowed, true),
                    $resource::canCreate(),
                    "Akses tombol tambah {$key} untuk role {$role} tidak sesuai.",
                );
            }
        }
    }

    public function test_custom_pages_and_hidden_navigation_follow_the_same_matrix(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $this->actingAs($teacher);
        $this->assertTrue(TeacherAttendance::canAccess());
        $this->assertTrue(ActivityReports::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::shouldRegisterNavigation());

        $student = User::role('siswa')->firstOrFail();
        $this->actingAs($student);
        $this->assertFalse(TeacherAttendance::canAccess());
        $this->assertFalse(ActivityReports::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(StudentResource::shouldRegisterNavigation());

        $parent = User::role('orang_tua')->firstOrFail();
        $this->actingAs($parent);
        $this->assertTrue(ActivityReports::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(UserNotificationResource::shouldRegisterNavigation());
    }
}
