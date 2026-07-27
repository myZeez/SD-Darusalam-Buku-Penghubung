<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_each_role_has_an_exact_and_separate_permission_set(): void
    {
        $allPermissions = [
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

        $this->assertEqualsCanonicalizing(User::BUSINESS_ROLES, Role::query()->pluck('name')->all());
        $this->assertRolePermissions('admin', array_values(array_diff($allPermissions, [
            'manage parent submissions',
        ])));
        $this->assertRolePermissions('loket', [
            'view arrivals',
            'manage arrivals',
        ]);
        $this->assertRolePermissions('guru', [
            'view teachers',
            'view students',
            'view classes',
            'view schedules',
            'manage schedules',
            'view school activities',
            'manage school activities',
            'view reports',
            'export reports',
            'manage students',
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
        $this->assertRolePermissions('orang_tua', [
            'view students',
            'view reports',
            'export reports',
            'view schedules',
            'view school activities',
            'view home activities',
            'view comments',
            'manage comments',
            'view notifications',
            'view attendances',
            'view parent submissions',
            'manage parent submissions',
            'view extracurriculars',
        ]);
        $this->assertRolePermissions('siswa', [
            'view students',
            'view extracurriculars',
        ]);

        $administrator = User::query()->where('email', 'admin@sddarusalam.test')->firstOrFail();
        $principal = User::query()->where('email', 'kepala.sekolah@sddarusalam.test')->firstOrFail();

        $this->assertTrue($administrator->isAdmin());
        $this->assertTrue($principal->isAdmin());
        $this->assertSame('Administrator Sekolah', $administrator->job_title);
        $this->assertSame('Kepala Sekolah', $principal->job_title);
    }

    public function test_the_generic_filament_profile_bypass_is_not_available_to_any_role(): void
    {
        $this->assertFalse(Route::has('filament.admin.auth.profile'));
        $siswa = User::role('siswa')->firstOrFail();

        $this->actingAs($siswa)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('/admin/profile', false);

        $this->actingAs($siswa)
            ->get('/admin/profile')
            ->assertNotFound();
    }

    public function test_navigation_uses_semantic_google_material_icons(): void
    {
        $this->assertSame('gmdi-dashboard-o', FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM));
        $this->assertSame('gmdi-manage-accounts-o', UserResource::getNavigationIcon());
        $this->assertSame('gmdi-school-o', TeacherResource::getNavigationIcon());
        $this->assertSame('gmdi-family-restroom-o', ParentProfileResource::getNavigationIcon());
        $this->assertSame('gmdi-class-o', SchoolClassResource::getNavigationIcon());
        $this->assertSame('gmdi-badge-o', StudentResource::getNavigationIcon());
        $this->assertSame('gmdi-assignment-o', SchoolActivityResource::getNavigationIcon());
        $this->assertSame('gmdi-home-work-o', HomeActivityResource::getNavigationIcon());
        $this->assertSame('gmdi-forum-o', ActivityCommentResource::getNavigationIcon());
        $this->assertSame('gmdi-calendar-month-o', ScheduleResource::getNavigationIcon());
        $this->assertSame('gmdi-notifications-o', UserNotificationResource::getNavigationIcon());
    }

    public function test_mobile_family_navigation_is_limited_to_parent_and_student_roles(): void
    {
        foreach (['orang_tua', 'siswa'] as $role) {
            $this->flushSession();

            $this->actingAs(User::role($role)->firstOrFail())
                ->get('/admin')
                ->assertOk()
                ->assertSee('family-mobile-nav', false);
        }

        foreach (['admin', 'guru'] as $role) {
            $this->flushSession();

            $this->actingAs(User::role($role)->firstOrFail())
                ->get('/admin')
                ->assertOk()
                ->assertDontSee('family-mobile-nav', false);
        }
    }

    public function test_admin_can_open_every_management_area(): void
    {
        $admin = User::role('admin')->firstOrFail();

        foreach ([
            '/admin/users',
            '/admin/users/create',
            '/admin/teachers',
            '/admin/teachers/create',
            '/admin/parent-profiles',
            '/admin/parent-profiles/create',
            '/admin/school-classes',
            '/admin/school-classes/create',
            '/admin/students',
            '/admin/students/create',
            '/admin/school-activities',
            '/admin/home-activities',
            '/admin/laporan-sekolah-harian',
            '/admin/aktivitas-rumah-harian',
            '/admin/activity-comments',
            '/admin/activity-comments/create',
            '/admin/schedules',
            '/admin/schedules/create',
            '/admin/user-notifications',
            '/admin/user-notifications/create',
            '/admin/parent-submissions',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)
            ->get('/reports/activity-summary')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_cannot_open_admin_or_parent_management_pages(): void
    {
        $guru = User::role('guru')->firstOrFail();

        $this->actingAs($guru);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/teachers/create')->assertForbidden();
        $this->get('/admin/parent-profiles')->assertForbidden();
        $this->get('/admin/school-classes/create')->assertForbidden();
        $this->get('/admin/students/create')->assertOk();
        $this->get('/admin/home-activities/create')->assertForbidden();
        $this->get('/admin/school-activities/create')->assertForbidden();
        $this->get('/admin/laporan-sekolah-harian')->assertOk();
        $this->get('/admin/aktivitas-rumah-harian')->assertOk();
        $this->get('/admin/activity-comments/create')->assertOk();
        $this->get('/admin/schedules/create')->assertOk();
        $this->get('/admin/user-notifications/create')->assertOk();
        $this->get('/admin/student-arrivals')->assertForbidden();
        $this->get('/admin/extracurriculars')->assertForbidden();
    }

    public function test_parent_cannot_open_admin_teacher_or_student_management_pages(): void
    {
        $orangTua = User::role('orang_tua')->firstOrFail();

        $this->actingAs($orangTua);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/teachers')->assertForbidden();
        $this->get('/admin/school-classes')->assertForbidden();
        $this->get('/admin/students/create')->assertForbidden();
        $this->get('/admin/school-activities/create')->assertForbidden();
        $this->get('/admin/schedules/create')->assertForbidden();
        $this->get('/admin/user-notifications/create')->assertForbidden();
        $this->get('/admin/home-activities/create')->assertForbidden();
        $this->get('/admin/activity-comments/create')->assertOk();
    }

    public function test_student_has_read_only_access_to_their_book(): void
    {
        $siswa = User::role('siswa')->firstOrFail();

        $this->actingAs($siswa);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/teachers')->assertForbidden();
        $this->get('/admin/parent-profiles')->assertForbidden();
        $this->get('/admin/school-classes')->assertForbidden();
        $this->get('/admin/students/create')->assertForbidden();
        $this->get('/admin/school-activities/create')->assertForbidden();
        $this->get('/admin/home-activities/create')->assertForbidden();
        $this->get('/admin/activity-comments/create')->assertForbidden();
        $this->get('/admin/schedules/create')->assertForbidden();
        $this->get('/admin/user-notifications/create')->assertForbidden();
        $this->get('/admin/school-activities')->assertForbidden();
        $this->get('/admin/home-activities')->assertForbidden();
        $this->get('/admin/activity-comments')->assertForbidden();
        $this->get('/admin/schedules')->assertForbidden();
        $this->get('/admin/user-notifications')->assertForbidden();
        $this->get('/admin/attendance-records')->assertForbidden();
        $this->get('/reports/activity-summary')->assertForbidden();
    }

    private function assertRolePermissions(string $role, array $expected): void
    {
        $actual = Role::findByName($role)
            ->permissions
            ->pluck('name')
            ->all();

        $this->assertEqualsCanonicalizing($expected, $actual, "Izin role {$role} tidak sesuai matriks akses.");
    }
}
