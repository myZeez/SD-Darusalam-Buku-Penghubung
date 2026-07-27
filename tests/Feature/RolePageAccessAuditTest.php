<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSecurity;
use App\Filament\Pages\ActivityReports;
use App\Filament\Pages\DailyHomeActivities;
use App\Filament\Pages\DailySchoolActivities;
use App\Filament\Pages\TeacherAttendance;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\EarlyDepartures\EarlyDepartureResource;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
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
        'school_settings' => SchoolSettingResource::class,
        'school_activities' => SchoolActivityResource::class,
        'home_activities' => HomeActivityResource::class,
        'comments' => ActivityCommentResource::class,
        'schedules' => ScheduleResource::class,
        'notifications' => UserNotificationResource::class,
        'arrivals' => StudentArrivalResource::class,
        'attendances' => AttendanceRecordResource::class,
        'early_departures' => EarlyDepartureResource::class,
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
            'admin' => ['users', 'teachers', 'parents', 'students', 'classes', 'school_settings', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'arrivals', 'attendances', 'early_departures', 'parent_submissions', 'extracurriculars', 'password_resets'],
            'loket' => ['arrivals'],
            'guru' => ['teachers', 'students', 'classes', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'early_departures', 'parent_submissions'],
            'orang_tua' => ['parents', 'students', 'school_activities', 'home_activities', 'comments', 'schedules', 'notifications', 'attendances', 'parent_submissions', 'extracurriculars'],
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
            'admin' => ['users', 'teachers', 'parents', 'students', 'classes', 'comments', 'schedules', 'notifications', 'arrivals', 'attendances', 'early_departures', 'extracurriculars'],
            'loket' => ['arrivals'],
            'guru' => ['students', 'comments', 'schedules', 'notifications', 'early_departures'],
            'orang_tua' => ['comments', 'parent_submissions'],
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
        $this->assertTrue(DailySchoolActivities::canAccess());
        $this->assertTrue(DailyHomeActivities::canAccess());
        $this->assertTrue(ActivityReports::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::shouldRegisterNavigation());

        $student = User::role('siswa')->firstOrFail();
        $this->actingAs($student);
        $this->assertFalse(TeacherAttendance::canAccess());
        $this->assertFalse(DailySchoolActivities::canAccess());
        $this->assertFalse(DailyHomeActivities::canAccess());
        $this->assertFalse(ActivityReports::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(StudentResource::shouldRegisterNavigation());

        $parent = User::role('orang_tua')->firstOrFail();
        $this->actingAs($parent);
        $this->assertTrue(ActivityReports::canAccess());
        $this->assertFalse(DailySchoolActivities::canAccess());
        $this->assertTrue(DailyHomeActivities::canAccess());
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(UserNotificationResource::shouldRegisterNavigation());
    }
}
