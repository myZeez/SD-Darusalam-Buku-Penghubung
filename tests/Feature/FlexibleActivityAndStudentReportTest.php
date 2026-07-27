<?php

namespace Tests\Feature;

use App\Filament\Pages\ActivityReports;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\HomeActivities\Pages\CreateHomeActivity;
use App\Filament\Resources\SchoolActivities\Pages\CreateSchoolActivity;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\AttendanceRecord;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\User;
use App\Services\StudentReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FlexibleActivityAndStudentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_teacher_can_create_school_activity_categories_with_checklists_and_text(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $student = $teacher->accessibleStudents()->firstOrFail();
        $groups = $this->schoolGroups();

        $this->actingAs($teacher);

        Livewire::test(CreateSchoolActivity::class)
            ->fillForm([
                'student_id' => $student->id,
                'activity_date' => today()->toDateString(),
                'attendance' => 'present',
                'activity_groups' => $groups,
                'note' => 'Kegiatan sekolah berjalan baik.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $activity = SchoolActivity::query()->latest('id')->firstOrFail();

        $this->assertSame($groups, $activity->activity_groups);
        $this->assertSame('Kegiatan Ibadah, Aspek Perkembangan', $activity->activity_category_summary);

        $this->get(SchoolActivityResource::getUrl('view', ['record' => $activity]))
            ->assertOk()
            ->assertSee('Kegiatan Ibadah')
            ->assertSee('Salat')
            ->assertSee('Sangat bersemangat dalam melakukan kegiatan outdoor.');
    }

    public function test_teacher_applies_the_fixed_home_activity_template_to_every_student_in_a_class(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $class = $teacher->managedClasses()->withCount('students')->firstOrFail();

        $this->actingAs($teacher);

        Livewire::test(CreateHomeActivity::class)
            ->fillForm([
                'class_id' => $class->id,
                'activity_date' => today()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $activities = HomeActivity::query()
            ->whereDate('activity_date', today())
            ->orderBy('student_id')
            ->get();
        $activity = $activities->firstOrFail();

        $this->assertCount($class->students_count, $activities);
        $this->assertSame(
            HomeActivity::defaultActivityGroupsForGrade($class->grade_level),
            $activity->activity_groups,
        );
        $this->assertSame('Berakhlak, Berprestasi, Berjiwa Sosial, Peduli Lingkungan', $activity->activity_category_summary);

        $this->get(HomeActivityResource::getUrl('view', ['record' => $activity]))
            ->assertOk()
            ->assertSee('Berakhlak')
            ->assertSee('Menjaga kebersihan dan kerapian rumah');
    }

    public function test_tahajud_is_included_only_for_the_upper_grades(): void
    {
        $lowerGradeLabels = collect(HomeActivity::defaultActivityGroupsForGrade(3))
            ->pluck('items')
            ->flatten(1)
            ->pluck('label');
        $upperGradeLabels = collect(HomeActivity::defaultActivityGroupsForGrade(4))
            ->pluck('items')
            ->flatten(1)
            ->pluck('label');

        $this->assertFalse($lowerGradeLabels->contains('Melaksanakan salat Tahajud'));
        $this->assertTrue($upperGradeLabels->contains('Melaksanakan salat Tahajud'));
    }

    public function test_admin_report_combines_attendance_arrival_and_both_activity_sources_in_pdf(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $teacher = User::role('guru')->firstOrFail();
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();

        $this->actingAs($admin);

        StudentArrival::create([
            'student_id' => $student->id,
            'recorded_by' => $admin->id,
            'arrival_date' => today(),
            'arrival_time' => '07:15:00',
        ]);
        $this->assertTrue(AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', today())
            ->where('status', 'present')
            ->where('is_late', true)
            ->exists());
        SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
            'activity_groups' => $this->schoolGroups(),
        ]);
        HomeActivity::create([
            'student_id' => $student->id,
            'parent_id' => $parent->parentProfile->id,
            'activity_date' => today(),
            'activity_groups' => $this->homeGroups(),
        ]);

        $report = app(StudentReportService::class)->build(
            $admin,
            today()->toDateString(),
            today()->toDateString(),
            $student->id,
        );

        $this->assertSame(1, $report['summary']['attendance']);
        $this->assertSame(1, $report['summary']['late']);
        $this->assertSame(1, $report['summary']['school_activities']);
        $this->assertSame(1, $report['summary']['home_activities']);

        $this->get(ActivityReports::getUrl())
            ->assertOk()
            ->assertSee('Presensi dan Kedatangan')
            ->assertSee('Terlambat')
            ->assertSee('Aktivitas Sekolah')
            ->assertSee('Aktivitas Rumah');

        $html = view('reports.activity-summary', [
            'report' => $report,
            'settings' => SchoolSetting::current(),
        ])->render();

        $this->assertStringContainsString('Sangat bersemangat dalam melakukan kegiatan outdoor.', $html);
        $this->assertStringContainsString('Guru Kelas', $html);
        $this->assertStringContainsString($teacher->name, $html);
        $this->assertStringContainsString('Orang Tua/Wali', $html);
        $this->assertStringContainsString($parent->name, $html);

        $this->get(route('reports.activity-summary', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
            'student_id' => $student->id,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_can_view_and_export_reports_only_for_accessible_students(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $accessibleStudent = $teacher->accessibleStudents()->firstOrFail();
        $otherStudent = Student::create([
            'nis' => 'SDD-LAPORAN-RAHASIA-001',
            'name' => 'Siswa di Luar Kelas Guru',
            'status' => 'active',
        ]);

        $this->actingAs($teacher);

        $this->assertTrue(ActivityReports::canAccess());

        $this->get(ActivityReports::getUrl())
            ->assertOk()
            ->assertSee($accessibleStudent->name)
            ->assertDontSee($otherStudent->name);

        $this->get(route('reports.activity-summary', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
            'student_id' => $accessibleStudent->id,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('reports.activity-summary', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
            'student_id' => $otherStudent->id,
        ]))->assertForbidden();
    }

    /** @return array<int, array<string, mixed>> */
    private function schoolGroups(): array
    {
        return [
            [
                'category' => 'Kegiatan Ibadah',
                'items' => [
                    ['label' => 'Salat', 'type' => 'checklist', 'checked' => true],
                    ['label' => 'Mengaji', 'type' => 'checklist', 'checked' => true],
                ],
            ],
            [
                'category' => 'Aspek Perkembangan',
                'items' => [
                    [
                        'label' => 'Kegiatan Outdoor',
                        'type' => 'text',
                        'checked' => null,
                        'text' => 'Sangat bersemangat dalam melakukan kegiatan outdoor.',
                    ],
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function homeGroups(): array
    {
        return [
            [
                'category' => 'Kebiasaan Harian',
                'items' => [
                    ['label' => 'Mengerjakan PR', 'type' => 'checklist', 'checked' => true],
                    [
                        'label' => 'Catatan Belajar',
                        'type' => 'text',
                        'text' => 'Belajar mandiri selama tiga puluh menit.',
                    ],
                ],
            ],
        ];
    }
}
