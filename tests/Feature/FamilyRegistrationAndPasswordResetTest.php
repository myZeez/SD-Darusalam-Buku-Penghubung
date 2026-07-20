<?php

namespace Tests\Feature;

use App\Actions\Auth\ProcessPasswordResetRequest;
use App\Filament\Pages\AccountSecurity;
use App\Filament\Pages\Auth\RegisterStudent;
use App\Filament\Pages\Auth\RequestPasswordHelp;
use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Models\ParentProfile;
use App\Models\PasswordResetRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FamilyRegistrationAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_creates_teacher_profile_and_login_account_in_one_form(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(CreateTeacher::class)
            ->fillForm([
                'profile_avatar' => null,
                'profile_name' => 'Guru Baru Darussalam',
                'profile_email' => 'guru.baru@sddarusalam.test',
                'profile_phone' => '081234567801',
                'profile_password' => 'PasswordGuru123',
                'profile_password_confirmation' => 'PasswordGuru123',
                'nip' => '197501012010011001',
                'gender' => 'female',
                'address' => 'Alamat Guru Baru',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'guru.baru@sddarusalam.test')->firstOrFail();
        $teacher = Teacher::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($user->hasRole('guru'));
        $this->assertTrue(Hash::check('PasswordGuru123', $user->password));
        $this->assertSame('Guru Baru Darussalam', $user->name);
        $this->assertSame('197501012010011001', $teacher->nip);
    }

    public function test_student_registration_creates_linked_student_and_parent_accounts_and_one_time_card(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Registrasi Siswa')
            ->assertSee('/admin/register', false)
            ->assertSee('/admin/password-reset/request', false);

        Livewire::test(RegisterStudent::class)
            ->fillForm([
                'nis' => 'SDD-REG-001',
                'name' => 'Siswa Registrasi',
                'email' => 'siswa.registrasi@sddarusalam.test',
                'student_phone' => '081234567802',
                'grade_level' => 1,
                'gender' => 'male',
                'birth_date' => '2019-02-03',
                'password' => 'PasswordKeluarga123',
                'passwordConfirmation' => 'PasswordKeluarga123',
                'parent_mode' => 'new',
                'parent_name' => 'Orang Tua Registrasi',
                'parent_email' => 'ortu.registrasi@sddarusalam.test',
                'parent_phone' => '081234567803',
                'father_name' => 'Ayah Registrasi',
                'mother_name' => 'Ibu Registrasi',
                'parent_address' => 'Alamat Keluarga Registrasi',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $studentUser = User::query()->where('email', 'siswa.registrasi@sddarusalam.test')->firstOrFail();
        $parentUser = User::query()->where('email', 'ortu.registrasi@sddarusalam.test')->firstOrFail();
        $student = Student::query()->where('user_id', $studentUser->id)->firstOrFail();

        $this->assertTrue($studentUser->hasRole('siswa'));
        $this->assertTrue($parentUser->hasRole('orang_tua'));
        $this->assertTrue(Hash::check('PasswordKeluarga123', $studentUser->password));
        $this->assertTrue(Hash::check('PasswordKeluarga123', $parentUser->password));
        $this->assertSame($parentUser->id, $student->parent->user_id);
        $this->assertSame(1, $student->class->grade_level);
        $this->assertAuthenticatedAs($studentUser);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('registration-credentials', false)
            ->assertSee('Registrasi Berhasil')
            ->assertSee('siswa.registrasi@sddarusalam.test')
            ->assertSee('ortu.registrasi@sddarusalam.test')
            ->assertSee('Simpan PNG')
            ->assertSee('Simpan JPG');

        $this->get('/admin')->assertDontSee('Registrasi Berhasil');
    }

    public function test_student_registration_can_link_a_second_child_to_an_existing_parent_account(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $parent = $parentUser->parentProfile;
        $profileCount = ParentProfile::query()->count();

        Livewire::test(RegisterStudent::class)
            ->fillForm([
                'nis' => 'SDD-REG-002',
                'name' => 'Adik Siswa Registrasi',
                'email' => 'adik.registrasi@sddarusalam.test',
                'student_phone' => '081234567804',
                'grade_level' => 1,
                'gender' => 'female',
                'birth_date' => '2019-06-03',
                'password' => 'PasswordAdik123',
                'passwordConfirmation' => 'PasswordAdik123',
                'parent_mode' => 'existing',
                'parent_email' => $parentUser->email,
                'parent_password' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $studentUser = User::query()->where('email', 'adik.registrasi@sddarusalam.test')->firstOrFail();
        $student = $studentUser->student;

        $this->assertSame($profileCount, ParentProfile::query()->count());
        $this->assertSame($parent->id, $student->parent_id);
        $this->assertTrue(Hash::check('password', $parentUser->fresh()->password));
        $this->assertTrue(Hash::check('PasswordAdik123', $studentUser->password));
        $this->assertTrue(session('registration_credentials.parent_uses_existing_password'));
        $this->assertNull(session('registration_credentials.parent_password'));
    }

    public function test_password_help_is_processed_by_admin_sent_to_teacher_and_must_be_changed(): void
    {
        $studentUser = User::role('siswa')->firstOrFail();
        $teacherUser = User::role('guru')->firstOrFail();

        Livewire::test(RequestPasswordHelp::class)
            ->fillForm([
                'email' => $studentUser->email,
                'request_note' => 'Lupa password akun siswa.',
            ])
            ->call('request')
            ->assertHasNoFormErrors();

        $request = PasswordResetRequest::query()->firstOrFail();
        $this->assertSame($studentUser->id, $request->user_id);
        $this->assertSame($studentUser->student->id, $request->student_id);
        $this->assertSame($teacherUser->id, $request->teacher_id);
        $this->assertSame('pending', $request->status);

        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);
        $temporaryPassword = app(ProcessPasswordResetRequest::class)->handle($request);

        $studentUser->refresh();
        $request->refresh();
        $this->assertTrue(Hash::check($temporaryPassword, $studentUser->password));
        $this->assertTrue($studentUser->must_change_password);
        $this->assertSame('processed', $request->status);
        $this->assertSame($admin->id, $request->processed_by);

        $teacherNotification = UserNotification::query()
            ->where('user_id', $teacherUser->id)
            ->where('title', 'like', 'Password Sementara:%')
            ->firstOrFail();
        $this->assertStringContainsString($studentUser->email, $teacherNotification->message);
        $this->assertStringContainsString($temporaryPassword, $teacherNotification->message);

        $this->flushSession();
        $this->actingAs($studentUser);
        $this->get('/admin')->assertRedirect(AccountSecurity::getUrl());

        Livewire::test(AccountSecurity::class)
            ->fillForm([
                'current_password' => $temporaryPassword,
                'password' => 'PasswordBaruKeluarga123',
                'password_confirmation' => 'PasswordBaruKeluarga123',
            ])
            ->call('changePassword')
            ->assertHasNoFormErrors();

        $studentUser->refresh();
        $this->assertFalse($studentUser->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaruKeluarga123', $studentUser->password));

        $this->flushSession();
        $this->actingAs($teacherUser);
        $this->assertFalse(PasswordResetRequestResource::canViewAny());
        $this->get(PasswordResetRequestResource::getUrl())->assertForbidden();

        $this->flushSession();
        $this->actingAs($admin);
        $this->assertTrue(PasswordResetRequestResource::canViewAny());
        $this->assertTrue(PasswordResetRequestResource::canProcess());
    }
}
