<?php

namespace Tests\Feature;

use App\Filament\Pages\ActivityReports;
use App\Filament\Pages\DailyHomeActivities;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParentDashboardAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_parent_dashboard_shows_profile_activity_recap_and_four_development_aspects(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();

        $this->actingAs($parent)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Ringkasan keluarga')
            ->assertSee('Profil orang tua')
            ->assertSee($parent->name)
            ->assertSee($student->name)
            ->assertSee('Rekap aktivitas')
            ->assertSee('Laporan sekolah')
            ->assertSee('Aktivitas rumah')
            ->assertSee('Spiritual dan Keagamaan')
            ->assertSee('Kognitif dan Akademik')
            ->assertSee('Sosial Emosional dan Karakter')
            ->assertSee('Fisik Motorik dan Kemandirian')
            ->assertDontSee('Laporan PDF')
            ->assertSee('Profil')
            ->assertSee('Laporan');
    }

    public function test_parent_can_view_reports_without_scores_or_pdf_downloads(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $otherStudent = Student::create([
            'class_id' => $student->class_id,
            'nis' => 'SDD-PDF-OUTSIDE',
            'name' => 'Siswa Luar Akses PDF',
            'status' => 'active',
        ]);

        $this->actingAs($parent);

        $this->assertTrue(ActivityReports::canAccess());
        $this->get(ActivityReports::getUrl())
            ->assertOk()
            ->assertSee('Laporan Anak')
            ->assertDontSee('Unduh PDF')
            ->assertDontSee('Penilaian Buku Penghubung');
        $this->get(DailyHomeActivities::getUrl())
            ->assertOk()
            ->assertDontSee('Export PDF')
            ->assertDontSee('Rekap nilai aktivitas rumah');
        $this->get(route('reports.activity-summary', ['student_id' => $student->id]))
            ->assertForbidden();
        $this->get(route('reports.activity-summary', ['student_id' => $otherStudent->id]))
            ->assertForbidden();
    }

    public function test_teacher_agenda_notifies_only_parents_of_the_target_class(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();

        Schedule::create([
            'class_id' => $student->class_id,
            'created_by' => $teacher->id,
            'title' => 'Kunjungan Perpustakaan',
            'activity_date' => today()->addWeek(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $parent->id,
            'created_by' => $teacher->id,
            'title' => 'Agenda Baru: Kunjungan Perpustakaan',
        ]);
        $this->assertSame(1, UserNotification::query()
            ->where('user_id', $parent->id)
            ->where('title', 'Agenda Baru: Kunjungan Perpustakaan')
            ->count());
    }
}
