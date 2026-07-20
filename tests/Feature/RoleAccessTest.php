<?php

namespace Tests\Feature;

use App\Actions\Students\SaveStudentWithFamily;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\HomeActivity;
use App\Models\ParentProfile;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_each_role_has_the_expected_resource_permissions(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $guru = User::role('guru')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $siswa = User::role('siswa')->firstOrFail();

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(StudentResource::canCreate());
        $this->assertTrue(SchoolActivityResource::canCreate());
        $this->assertTrue(HomeActivityResource::canCreate());
        $this->assertTrue(ActivityCommentResource::canCreate());
        $this->assertTrue(ScheduleResource::canCreate());
        $this->assertTrue(UserNotificationResource::canCreate());

        $this->actingAs($guru);
        $this->assertFalse(UserResource::canViewAny());
        $this->assertTrue(TeacherResource::canViewAny());
        $this->assertTrue(SchoolClassResource::canViewAny());
        $this->assertTrue(StudentResource::canCreate());
        $this->assertTrue(SchoolActivityResource::canCreate());
        $this->assertFalse(HomeActivityResource::canCreate());
        $this->assertTrue(ActivityCommentResource::canCreate());
        $this->assertTrue(ScheduleResource::canViewAny());
        $this->assertTrue(ScheduleResource::canCreate());
        $this->assertTrue(UserNotificationResource::canViewAny());
        $this->assertTrue(UserNotificationResource::canCreate());

        $this->actingAs($orangTua);
        $this->assertFalse(UserResource::canViewAny());
        $this->assertTrue(ParentProfileResource::canViewAny());
        $this->assertFalse(TeacherResource::canViewAny());
        $this->assertFalse(SchoolClassResource::canViewAny());
        $this->assertFalse(StudentResource::canCreate());
        $this->assertTrue(SchoolActivityResource::canViewAny());
        $this->assertFalse(SchoolActivityResource::canCreate());
        $this->assertTrue(HomeActivityResource::canCreate());
        $this->assertTrue(ActivityCommentResource::canCreate());
        $this->assertTrue(ScheduleResource::canViewAny());
        $this->assertFalse(ScheduleResource::canCreate());
        $this->assertTrue(UserNotificationResource::canViewAny());
        $this->assertFalse(UserNotificationResource::canCreate());

        $this->actingAs($siswa);
        $this->assertFalse(UserResource::canViewAny());
        $this->assertTrue(StudentResource::canViewAny());
        $this->assertFalse(StudentResource::canCreate());
        $this->assertFalse(SchoolActivityResource::canCreate());
        $this->assertFalse(HomeActivityResource::canCreate());
        $this->assertFalse(ActivityCommentResource::canCreate());
        $this->assertFalse(ScheduleResource::canViewAny());
        $this->assertFalse(ScheduleResource::canCreate());
        $this->assertFalse(UserNotificationResource::canViewAny());
        $this->assertFalse(UserNotificationResource::canCreate());
    }

    public function test_student_queries_are_scoped_to_each_users_relationship(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $guru = User::role('guru')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $siswa = User::role('siswa')->firstOrFail();
        $ownStudent = Student::where('user_id', $siswa->id)->firstOrFail();

        $unrelatedUser = User::factory()->create([
            'email' => 'siswa-lain@sddarusalam.test',
            'status' => 'active',
        ]);
        $unrelatedUser->assignRole('siswa');

        $unrelatedStudent = Student::create([
            'user_id' => $unrelatedUser->id,
            'nis' => 'SDD-002',
            'name' => 'Siswa Lain',
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->assertEqualsCanonicalizing([$ownStudent->id, $unrelatedStudent->id], StudentResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($guru);
        $this->assertSame([$ownStudent->id], StudentResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($orangTua);
        $this->assertSame([$ownStudent->id], StudentResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($siswa);
        $this->assertSame([$ownStudent->id], StudentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertFalse(StudentResource::canView($unrelatedStudent));
    }

    public function test_activity_records_are_scoped_and_only_the_owner_can_edit_them(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $siswa = User::role('siswa')->firstOrFail();
        $student = Student::where('user_id', $siswa->id)->firstOrFail();
        $teacher = Teacher::where('user_id', $guru->id)->firstOrFail();
        $parent = ParentProfile::where('user_id', $orangTua->id)->firstOrFail();

        $schoolActivity = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);

        $homeActivity = HomeActivity::create([
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'activity_date' => today(),
        ]);

        $this->actingAs($guru);
        $this->assertTrue(SchoolActivityResource::canEdit($schoolActivity));
        $this->assertFalse(HomeActivityResource::canEdit($homeActivity));

        $this->actingAs($orangTua);
        $this->assertTrue(SchoolActivityResource::canView($schoolActivity));
        $this->assertFalse(SchoolActivityResource::canEdit($schoolActivity));
        $this->assertTrue(HomeActivityResource::canEdit($homeActivity));

        $this->actingAs($siswa);
        $this->assertFalse(SchoolActivityResource::canView($schoolActivity));
        $this->assertFalse(HomeActivityResource::canView($homeActivity));
        $this->assertFalse(SchoolActivityResource::canEdit($schoolActivity));
        $this->assertFalse(HomeActivityResource::canEdit($homeActivity));
    }

    public function test_restricted_routes_cannot_be_opened_directly(): void
    {
        $siswa = User::role('siswa')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $guru = User::role('guru')->firstOrFail();
        $parentStudent = $orangTua->accessibleStudents()->firstOrFail();

        $this->actingAs($siswa)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($orangTua)
            ->get('/reports/activity-summary?student_id='.$parentStudent->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($guru)
            ->get('/reports/activity-summary')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_parent_can_open_only_their_own_parent_profile(): void
    {
        $orangTua = User::role('orang_tua')->firstOrFail();
        $profile = $orangTua->parentProfile;

        $this->actingAs($orangTua)
            ->get('/admin/teachers')
            ->assertForbidden();

        $this->actingAs($orangTua)
            ->get('/admin/school-activities/create')
            ->assertForbidden();

        $this->actingAs($orangTua)
            ->get('/admin/parent-profiles')
            ->assertOk();

        $this->actingAs($orangTua)
            ->get(ParentProfileResource::getUrl('view', ['record' => $profile]))
            ->assertOk();

        $this->actingAs($orangTua)
            ->get('/admin/parent-profiles/create')
            ->assertForbidden();
    }

    public function test_teacher_cannot_create_home_activities(): void
    {
        $guru = User::role('guru')->firstOrFail();

        $this->actingAs($guru)
            ->get('/admin/home-activities/create')
            ->assertForbidden();
    }

    public function test_student_cannot_open_create_pages(): void
    {
        $siswa = User::role('siswa')->firstOrFail();

        $this->actingAs($siswa)
            ->get('/admin/activity-comments/create')
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get('/admin/user-notifications/create')
            ->assertForbidden();
    }

    public function test_student_navigation_only_contains_allowed_resources(): void
    {
        $siswa = User::role('siswa')->firstOrFail();

        $this->actingAs($siswa)
            ->get('/admin')
            ->assertOk()
            ->assertSee('/admin/students', false)
            ->assertSee('/admin/extracurriculars', false)
            ->assertDontSee('/admin/school-activities', false)
            ->assertDontSee('/admin/home-activities', false)
            ->assertDontSee('/admin/activity-comments', false)
            ->assertDontSee('/admin/schedules', false)
            ->assertDontSee('/admin/user-notifications', false)
            ->assertDontSee('/admin/attendance-records', false)
            ->assertDontSee('/admin/keamanan-akun', false)
            ->assertDontSee('/admin/users', false)
            ->assertDontSee('/admin/teachers', false)
            ->assertDontSee('/admin/parent-profiles', false)
            ->assertDontSee('/admin/school-classes', false);
    }

    public function test_teacher_navigation_only_contains_the_class_workspace(): void
    {
        $guru = User::role('guru')->firstOrFail();

        $this->actingAs($guru)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Profil Guru')
            ->assertSee('Kelas dan Siswa')
            ->assertSee('/admin/school-activities', false)
            ->assertSee('/admin/home-activities', false)
            ->assertSee('/admin/activity-comments', false)
            ->assertSee('/admin/laporan-aktivitas', false)
            ->assertDontSee('/admin/student-arrivals', false)
            ->assertDontSee('/admin/extracurriculars', false)
            ->assertDontSee('/admin/keamanan-akun', false)
            ->assertDontSee('/admin/users', false)
            ->assertSee('/admin/students', false)
            ->assertDontSee('/admin/parent-profiles', false);
    }

    public function test_parent_navigation_only_contains_the_child_workspace(): void
    {
        $orangTua = User::role('orang_tua')->firstOrFail();

        $this->actingAs($orangTua)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Profil Orang Tua')
            ->assertSee('Anak Saya')
            ->assertSee('/admin/students', false)
            ->assertSee("/admin/parent-profiles/{$orangTua->parentProfile->id}", false)
            ->assertSee('/admin/school-activities', false)
            ->assertSee('/admin/home-activities', false)
            ->assertSee('/admin/activity-comments', false)
            ->assertSee('/admin/attendance-records', false)
            ->assertSee('/admin/laporan-aktivitas', false)
            ->assertDontSee('/admin/keamanan-akun', false)
            ->assertDontSee('/admin/users', false)
            ->assertDontSee('/admin/teachers', false)
            ->assertDontSee('/admin/school-classes', false);
    }

    public function test_student_and_new_parent_accounts_are_created_and_linked_together(): void
    {
        $class = SchoolClass::query()->firstOrFail();

        $student = app(SaveStudentWithFamily::class)->create([
            'class_id' => $class->id,
            'nis' => 'SDD-NEW-001',
            'name' => 'Siswa Keluarga Baru',
            'gender' => 'male',
            'birth_date' => '2018-01-10',
            'status' => 'active',
            'student_email' => 'siswa-keluarga-baru@sddarusalam.test',
            'student_phone' => '081111111111',
            'student_password' => 'password-baru',
            'parent_mode' => 'new',
            'parent_name' => 'Orang Tua Keluarga Baru',
            'parent_email' => 'ortu-keluarga-baru@sddarusalam.test',
            'parent_phone' => '082222222222',
            'parent_password' => 'password-orang-tua',
            'father_name' => 'Ayah Baru',
            'mother_name' => 'Ibu Baru',
            'parent_address' => 'Alamat keluarga baru',
        ]);

        $student->load(['user.roles', 'parent.user.roles']);

        $this->assertTrue($student->user->hasRole('siswa'));
        $this->assertTrue($student->parent->user->hasRole('orang_tua'));
        $this->assertSame('siswa-keluarga-baru@sddarusalam.test', $student->user->email);
        $this->assertSame('ortu-keluarga-baru@sddarusalam.test', $student->parent->user->email);
        $this->assertTrue($student->user->canAccessStudent($student));
        $this->assertTrue($student->parent->user->canAccessStudent($student));
    }

    public function test_an_existing_parent_account_can_be_linked_to_multiple_students(): void
    {
        $parent = ParentProfile::query()->withCount('students')->firstOrFail();
        $class = SchoolClass::query()->firstOrFail();
        $parentUserCount = User::role('orang_tua')->count();

        $student = app(SaveStudentWithFamily::class)->create([
            'class_id' => $class->id,
            'nis' => 'SDD-NEW-002',
            'name' => 'Adik Siswa',
            'gender' => 'female',
            'birth_date' => '2019-02-11',
            'status' => 'active',
            'student_email' => 'adik-siswa@sddarusalam.test',
            'student_password' => 'password-adik',
            'parent_mode' => 'existing',
            'parent_id' => $parent->id,
        ]);

        $this->assertSame($parent->id, $student->parent_id);
        $this->assertSame($parentUserCount, User::role('orang_tua')->count());
        $this->assertSame($parent->students_count + 1, $parent->students()->count());
        $this->assertTrue($parent->user->canAccessStudent($student));
    }

    public function test_navigation_labels_follow_the_active_role(): void
    {
        $this->actingAs(User::role('admin')->firstOrFail());
        $this->assertSame('Siswa dan Orang Tua', StudentResource::getNavigationLabel());
        $this->assertTrue(ParentProfileResource::shouldRegisterNavigation());

        $this->actingAs(User::role('guru')->firstOrFail());
        $this->assertSame('Siswa Kelas Saya', StudentResource::getNavigationLabel());
        $this->assertSame('Profil Guru', TeacherResource::getNavigationLabel());
        $this->assertSame('Kelas dan Siswa', SchoolClassResource::getNavigationLabel());

        $this->actingAs(User::role('orang_tua')->firstOrFail());
        $this->assertSame('Anak Saya', StudentResource::getNavigationLabel());
        $this->assertSame('Profil Orang Tua', ParentProfileResource::getNavigationLabel());

        $this->actingAs(User::role('siswa')->firstOrFail());
        $this->assertSame('Profil Saya', StudentResource::getNavigationLabel());
    }

    public function test_admin_student_form_combines_student_and_parent_accounts(): void
    {
        $this->actingAs(User::role('admin')->firstOrFail())
            ->get('/admin/students/create')
            ->assertOk()
            ->assertSee('Identitas Siswa')
            ->assertSee('Akun Siswa')
            ->assertSee('Hubungkan Orang Tua')
            ->assertSee('Orang Tua Terdaftar')
            ->assertSee('Buat Orang Tua Baru');
    }

    public function test_admin_can_submit_the_combined_student_and_parent_form(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $class = SchoolClass::query()->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(CreateStudent::class)
            ->fillForm([
                'class_id' => $class->id,
                'nis' => 'SDD-LIVEWIRE-001',
                'name' => 'Siswa Dari Form',
                'gender' => 'male',
                'birth_date' => '2018-03-12',
                'status' => 'active',
                'student_email' => 'siswa-form@sddarusalam.test',
                'student_phone' => '081234567890',
                'student_password' => 'password-siswa',
                'student_password_confirmation' => 'password-siswa',
                'parent_mode' => 'new',
                'parent_name' => 'Orang Tua Dari Form',
                'parent_email' => 'ortu-form@sddarusalam.test',
                'parent_phone' => '089876543210',
                'parent_password' => 'password-orang-tua',
                'parent_password_confirmation' => 'password-orang-tua',
                'father_name' => 'Ayah Dari Form',
                'mother_name' => 'Ibu Dari Form',
                'parent_address' => 'Alamat dari form gabungan',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = Student::query()->where('nis', 'SDD-LIVEWIRE-001')->firstOrFail();

        $this->assertSame('siswa-form@sddarusalam.test', $student->user->email);
        $this->assertSame('ortu-form@sddarusalam.test', $student->parent->user->email);
        $this->assertTrue($student->user->hasRole('siswa'));
        $this->assertTrue($student->parent->user->hasRole('orang_tua'));
    }
}
