<?php

namespace Tests\Feature;

use App\Filament\Pages\ActivityReports;
use App\Filament\Pages\DailyHomeActivities;
use App\Filament\Pages\DailySchoolActivities;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\AttendanceRecord;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\User;
use App\Services\ActivityScoreService;
use App\Services\StudentReportService;
use App\Support\SchoolActivityTemplate;
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

    public function test_teacher_can_fill_the_fixed_school_checklist_for_a_whole_class(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $class = $teacher->managedClasses()->withCount('students')->firstOrFail();
        $date = now()->startOfWeek()->toDateString();

        $this->actingAs($teacher);

        Livewire::test(DailySchoolActivities::class)
            ->set('selectedClassId', $class->id)
            ->set('selectedDate', $date)
            ->call('markActivityForAll', 'duha-prayer', true)
            ->call('saveChecklist')
            ->assertHasNoErrors();

        $activities = SchoolActivity::query()
            ->whereDate('activity_date', $date)
            ->whereHas('student', fn ($query) => $query->where('class_id', $class->id))
            ->get();

        $this->assertCount($class->students_count, $activities);
        $this->assertSame(
            collect(SchoolActivityTemplate::forGrade($class->grade_level))->pluck('category')->all(),
            collect($activities->firstOrFail()->activity_groups)->pluck('category')->all(),
        );
        $this->assertTrue($activities->every(fn (SchoolActivity $activity): bool => collect($activity->activity_groups)
            ->flatMap(fn (array $group) => $group['items'])
            ->firstWhere('key', 'duha-prayer')['checked']));

        $this->assertFalse(SchoolActivityResource::canCreate());
        $this->assertFalse(SchoolActivityResource::canDeleteAny());
    }

    public function test_parent_gets_the_fixed_home_checklist_without_teacher_creating_it_first(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();

        $this->actingAs($parent);

        Livewire::test(DailyHomeActivities::class)
            ->set('selectedStudentId', $student->id)
            ->set('selectedDate', today()->toDateString())
            ->call('markAll', true)
            ->set('note', 'Checklist diisi dari halaman aktivitas rumah harian.')
            ->call('saveChecklist')
            ->assertHasNoErrors();

        $activity = HomeActivity::query()
            ->where('student_id', $student->id)
            ->whereDate('activity_date', today())
            ->firstOrFail();

        $this->assertSame(
            collect(HomeActivity::defaultActivityGroupsForGrade($student->class->grade_level))->pluck('category')->all(),
            collect($activity->activity_groups)->pluck('category')->all(),
        );
        $this->assertNotNull($activity->submitted_at);
        $this->assertSame($parent->id, $activity->submitted_by);
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

    public function test_school_weekly_score_uses_the_admin_school_days_setting(): void
    {
        SchoolSetting::current()->update([
            'school_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
        ]);

        $scores = app(ActivityScoreService::class)->summarize(
            collect(),
            SchoolActivityTemplate::forGrade(1),
            '2026-07-27',
            '2026-08-02',
            'school',
        );

        $itemCount = collect(SchoolActivityTemplate::forGrade(1))
            ->sum(fn (array $group): int => count($group['items']));

        $this->assertSame($itemCount * 6 * 5, collect($scores)->sum('maximum_score'));
    }

    public function test_activity_score_converts_monthly_percentages_to_a_through_d(): void
    {
        $this->assertSame('A', ActivityScoreService::rating(85));
        $this->assertSame('B', ActivityScoreService::rating(84));
        $this->assertSame('C', ActivityScoreService::rating(69));
        $this->assertSame('D', ActivityScoreService::rating(54));
        $this->assertSame('Sangat Baik', ActivityScoreService::ratingLabel(100));
        $this->assertSame('Perlu Bimbingan', ActivityScoreService::ratingLabel(0));
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

    public function test_teacher_can_export_the_daily_school_checklist_as_pdf(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $schoolClass = $teacher->managedClasses()->with('students')->firstOrFail();
        $student = $schoolClass->students()->where('status', 'active')->firstOrFail();

        $schoolActivity = SchoolActivity::query()
            ->where('student_id', $student->id)
            ->whereDate('activity_date', today())
            ->first();

        if ($schoolActivity) {
            $schoolActivity->update([
                'teacher_id' => $teacher->teacher->id,
                'attendance' => 'present',
                'activity_groups' => $this->schoolGroups(),
            ]);
        } else {
            SchoolActivity::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->teacher->id,
                'activity_date' => today()->toDateString(),
                'attendance' => 'present',
                'activity_groups' => $this->schoolGroups(),
            ]);
        }

        $this->actingAs($teacher)
            ->get(route('reports.daily-school-activities', [
                'class_id' => $schoolClass->id,
                'date' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_parent_cannot_export_daily_home_activity_as_pdf(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->with('class')->firstOrFail();
        $otherStudent = Student::query()
            ->where('id', '!=', $student->id)
            ->where('status', 'active')
            ->firstOrFail();

        $homeActivity = HomeActivity::query()
            ->where('student_id', $student->id)
            ->whereDate('activity_date', today())
            ->first();

        if ($homeActivity) {
            $homeActivity->update([
                'parent_id' => $student->parent_id,
                'activity_groups' => $this->homeGroups(),
                'submitted_at' => now(),
                'submitted_by' => $parent->id,
            ]);
        } else {
            HomeActivity::query()->create([
                'student_id' => $student->id,
                'parent_id' => $student->parent_id,
                'activity_date' => today()->toDateString(),
                'activity_groups' => $this->homeGroups(),
                'submitted_at' => now(),
                'submitted_by' => $parent->id,
            ]);
        }

        $this->actingAs($parent)
            ->get(route('reports.daily-home-activities', [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
            ]))
            ->assertForbidden();

        $this->get(route('reports.daily-home-activities', [
            'student_id' => $otherStudent->id,
            'date' => today()->toDateString(),
        ]))->assertForbidden();
    }

    public function test_teacher_can_export_the_daily_home_activity_recap_for_a_managed_class_as_pdf(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $schoolClass = $teacher->managedClasses()->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('reports.daily-home-activities', [
                'class_id' => $schoolClass->id,
                'date' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
