<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSecurity;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Filament\Widgets\DashboardStats;
use App\Models\AttendanceRecord;
use App\Models\Extracurricular;
use App\Models\ExtracurricularEnrollment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_student_navigation_contains_dashboard_profile_and_extracurricular(): void
    {
        $studentUser = User::role('siswa')->firstOrFail();
        $student = $studentUser->student;

        $this->actingAs($studentUser);

        $this->assertTrue(StudentResource::canView($student));
        $this->assertFalse(StudentResource::canEdit($student));
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(AttendanceRecordResource::shouldRegisterNavigation());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('family-mobile-nav', false)
            ->assertSee('Profil')
            ->assertSee('Ekskul')
            ->assertSee("/admin/students/{$student->id}", false)
            ->assertSee('/admin/extracurriculars', false)
            ->assertDontSee('/admin/lesson-schedules', false)
            ->assertDontSee('/admin/school-activities', false)
            ->assertDontSee('/admin/home-activities', false)
            ->assertDontSee('/admin/activity-comments', false)
            ->assertDontSee('/admin/schedules', false)
            ->assertDontSee('/admin/user-notifications', false)
            ->assertDontSee('/admin/attendance-records', false)
            ->assertDontSee('/admin/keamanan-akun', false);

        $this->get(StudentResource::getNavigationUrl())
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee($student->name)
            ->assertSee($student->class->name)
            ->assertDontSee(StudentResource::getUrl('edit', ['record' => $student]), false);

    }

    public function test_student_cannot_open_removed_operational_pages_directly(): void
    {
        $studentUser = User::role('siswa')->firstOrFail();

        $this->actingAs($studentUser);

        foreach ([
            SchoolActivityResource::class,
            HomeActivityResource::class,
            ActivityCommentResource::class,
            ScheduleResource::class,
            UserNotificationResource::class,
            AttendanceRecordResource::class,
        ] as $resource) {
            $this->assertFalse($resource::canViewAny());
            $this->get($resource::getUrl())->assertForbidden();
        }

        $this->assertTrue(ExtracurricularResource::canViewAny());
        $this->get(ExtracurricularResource::getUrl())->assertOk();
    }

    public function test_student_dashboard_only_shows_today_attendance_and_extracurricular_count(): void
    {
        $studentUser = User::role('siswa')->firstOrFail();
        $student = $studentUser->student;
        $extracurricular = Extracurricular::create([
            'teacher_id' => $student->class->teacher_id,
            'name' => 'Pramuka',
            'capacity' => 30,
            'status' => 'active',
        ]);

        AttendanceRecord::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'attendance_date' => today(),
            'status' => 'present',
            'source' => 'teacher',
        ]);
        ExtracurricularEnrollment::create([
            'extracurricular_id' => $extracurricular->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $this->actingAs($studentUser);

        $widget = new class extends DashboardStats
        {
            public function statsForTest(): array
            {
                return $this->getStats();
            }
        };

        $stats = collect($widget->statsForTest())
            ->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()]);

        $this->assertSame(['Presensi Hari Ini', 'Ekstrakurikuler Diikuti'], $stats->keys()->all());
        $this->assertSame('Hadir', $stats->get('Presensi Hari Ini'));
        $this->assertSame(1, $stats->get('Ekstrakurikuler Diikuti'));
    }
}
