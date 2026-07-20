<?php

namespace Tests\Feature;

use App\Filament\Widgets\PendingSchoolReports;
use App\Models\SchoolActivity;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PendingSchoolReportsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_teacher_dashboard_lists_only_managed_students_without_a_report_today(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $reportedStudent = $teacher->managedStudents()
            ->where('status', 'active')
            ->whereDoesntHave('schoolActivities', fn ($query) => $query->whereDate('activity_date', today()))
            ->firstOrFail();
        $pendingCountBefore = $teacher->managedStudents()
            ->where('status', 'active')
            ->whereDoesntHave('schoolActivities', fn ($query) => $query->whereDate('activity_date', today()))
            ->count();
        $pendingStudent = Student::create([
            'class_id' => $reportedStudent->class_id,
            'nis' => 'SDD-PENDING-REPORT-001',
            'name' => 'Siswa Belum Dilaporkan',
            'status' => 'active',
        ]);
        SchoolActivity::create([
            'student_id' => $reportedStudent->id,
            'teacher_id' => $teacher->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);

        $this->actingAs($teacher);

        $widget = new class extends PendingSchoolReports
        {
            public function dataForTest(): array
            {
                return $this->getViewData();
            }
        };
        $data = $widget->dataForTest();

        $this->assertTrue(PendingSchoolReports::canView());
        $this->assertSame(
            $pendingCountBefore,
            $data['pendingStudents']->count(),
        );
        $this->assertTrue($data['pendingStudents']->contains('id', $pendingStudent->id));
        $this->assertFalse($data['pendingStudents']->contains('id', $reportedStudent->id));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Laporan sekolah hari ini')
            ->assertSee('Siswa Belum Dilaporkan');
    }

    public function test_pending_school_reports_widget_is_hidden_for_non_teachers(): void
    {
        $this->actingAs(User::role('orang_tua')->firstOrFail());

        $this->assertFalse(PendingSchoolReports::canView());
    }
}
