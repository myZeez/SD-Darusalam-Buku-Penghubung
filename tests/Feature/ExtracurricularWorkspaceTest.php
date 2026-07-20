<?php

namespace Tests\Feature;

use App\Filament\Pages\ActivityReports;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Widgets\DashboardStats;
use App\Models\AttendanceRecord;
use App\Models\Extracurricular;
use App\Models\ExtracurricularEnrollment;
use App\Models\ExtracurricularScore;
use App\Models\ExtracurricularSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExtracurricularWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_extracurricular_is_managed_by_admin_and_scoped_to_linked_family(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $teacherUser = User::role('guru')->firstOrFail();
        $parentUser = User::role('orang_tua')->firstOrFail();
        $studentUser = User::role('siswa')->firstOrFail();
        $gateUser = User::role('loket')->firstOrFail();
        $student = $studentUser->student;

        $ownExtracurricular = Extracurricular::create([
            'teacher_id' => $teacherUser->teacher->id,
            'name' => 'Pramuka',
            'day_of_week' => 'friday',
            'capacity' => 30,
            'status' => 'active',
        ]);
        ExtracurricularEnrollment::create([
            'extracurricular_id' => $ownExtracurricular->id,
            'student_id' => $student->id,
            'joined_at' => today(),
            'status' => 'active',
        ]);

        $otherTeacher = $this->createOtherTeacher();
        $otherExtracurricular = Extracurricular::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Futsal',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->assertTrue(ExtracurricularResource::canCreate());
        $this->assertTrue(ExtracurricularResource::canEdit($ownExtracurricular));

        $this->flushSession();
        $this->actingAs($teacherUser);
        $this->assertFalse(ExtracurricularResource::canViewAny());
        $this->assertFalse(ExtracurricularResource::canCreate());
        $this->assertFalse(ExtracurricularResource::canEdit($ownExtracurricular));
        $this->assertFalse(ExtracurricularResource::canEdit($otherExtracurricular));
        $this->get(ExtracurricularResource::getUrl())->assertForbidden();
        $this->get(ExtracurricularResource::getUrl('create'))->assertForbidden();

        $this->flushSession();
        $this->actingAs($parentUser);
        $this->assertSame([$ownExtracurricular->id], ExtracurricularResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(ExtracurricularResource::canView($ownExtracurricular));
        $this->assertFalse(ExtracurricularResource::canEdit($ownExtracurricular));

        $this->flushSession();
        $this->actingAs($studentUser);
        $this->assertSame([$ownExtracurricular->id], ExtracurricularResource::getEloquentQuery()->pluck('id')->all());
        $this->get(ExtracurricularResource::getUrl('view', ['record' => $ownExtracurricular]))
            ->assertOk()
            ->assertSee('Pramuka')
            ->assertSee('Pemantauan Anak')
            ->assertDontSee('Peserta Ekstrakurikuler')
            ->assertDontSee('Sesi, Materi, dan Absensi')
            ->assertDontSee('Materi dan Nilai');

        $this->flushSession();
        $this->actingAs($gateUser);
        $this->assertFalse(ExtracurricularResource::canViewAny());
        $this->get(ExtracurricularResource::getUrl())->assertForbidden();
    }

    public function test_session_creates_student_attendance_and_stores_material_photo_and_score(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $teacherUser = User::role('guru')->firstOrFail();
        $student = Student::query()->firstOrFail();
        $extracurricular = Extracurricular::create([
            'teacher_id' => $teacherUser->teacher->id,
            'name' => 'Tahfidz',
            'capacity' => 25,
            'status' => 'active',
        ]);
        ExtracurricularEnrollment::create([
            'extracurricular_id' => $extracurricular->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $session = ExtracurricularSession::create([
            'extracurricular_id' => $extracurricular->id,
            'teacher_id' => $teacherUser->teacher->id,
            'session_date' => today(),
            'title' => 'Hafalan Surat Pendek',
            'material' => 'Surat Al-Fil dan Al-Humazah',
            'photo' => 'extracurriculars/tahfidz.jpg',
            'coach_attendance_status' => 'present',
        ]);
        ExtracurricularScore::create([
            'extracurricular_id' => $extracurricular->id,
            'student_id' => $student->id,
            'title' => 'Kelancaran Hafalan',
            'score' => 88,
            'assessed_at' => today(),
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('extracurricular_scores', [
            'extracurricular_id' => $extracurricular->id,
            'student_id' => $student->id,
            'score' => 88,
            'recorded_by' => $admin->id,
        ]);
    }

    public function test_teacher_report_page_and_dashboard_attendance_are_available(): void
    {
        $teacherUser = User::role('guru')->firstOrFail();
        $studentUser = User::role('siswa')->firstOrFail();
        $student = $studentUser->student;

        AttendanceRecord::create([
            'student_id' => $student->id,
            'attendance_date' => today(),
            'status' => 'present',
            'source' => 'teacher',
        ]);

        $this->actingAs($teacherUser);
        $this->assertTrue(ActivityReports::canAccess());
        $this->get(ActivityReports::getUrl())
            ->assertOk();
        $this->actingAs($teacherUser);
        $teacherStats = $this->dashboardStats();
        $this->assertSame('1 / 1', $teacherStats->get('Presensi Kelas Hari Ini'));

        $this->actingAs($studentUser);
        $this->assertFalse(ActivityReports::canAccess());
        $this->actingAs($studentUser);
        $studentStats = $this->dashboardStats();
        $this->assertSame('Hadir', $studentStats->get('Presensi Hari Ini'));
    }

    private function createOtherTeacher(): Teacher
    {
        $user = User::factory()->create([
            'email' => 'pembina.lain@sddarusalam.test',
            'status' => 'active',
        ]);
        $user->assignRole('guru');

        return Teacher::create([
            'user_id' => $user->id,
            'nip' => '197001012000011088',
        ]);
    }

    private function dashboardStats()
    {
        $widget = new class extends DashboardStats
        {
            public function statsForTest(): array
            {
                return $this->getStats();
            }
        };

        return collect($widget->statsForTest())
            ->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()]);
    }
}
