<?php

namespace Tests\Feature;

use App\Filament\Resources\HomeActivities\Pages\ListHomeActivities;
use App\Filament\Resources\SchoolActivities\Pages\ListSchoolActivities;
use App\Filament\Resources\SchoolActivities\Schemas\SchoolActivityForm;
use App\Models\SchoolActivity;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SchoolActivityObservationAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_school_template_matches_the_four_fixed_book_categories(): void
    {
        $groups = SchoolActivityForm::defaultActivityGroups();

        $this->assertSame([
            'Berakhlak',
            'Berprestasi',
            'Berjiwa Sosial',
            'Peduli Lingkungan',
        ], array_column($groups, 'category'));

        $labels = collect($groups)
            ->pluck('items')
            ->flatten(1)
            ->pluck('label')
            ->all();

        $this->assertCount(21, $labels);
        $this->assertContains('Hadir di sekolah tepat waktu', $labels);
        $this->assertContains('Melaksanakan salat Duha', $labels);
        $this->assertContains('Membiasakan 5S (Senyum, Salam, Sapa, Sopan, Santun)', $labels);
        $this->assertContains('Menjaga dan merawat lingkungan sekolah', $labels);
    }

    public function test_teacher_can_filter_school_reports_by_student_and_date_range(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $class = $teacher->accessibleClasses()->firstOrFail();
        $matchingStudent = Student::create([
            'class_id' => $class->id,
            'nis' => 'SDD-FILTER-SEKOLAH-001',
            'name' => 'Siswa Filter Laporan Sekolah',
            'status' => 'active',
        ]);
        $otherStudent = Student::create([
            'class_id' => $class->id,
            'nis' => 'SDD-FILTER-SEKOLAH-002',
            'name' => 'Siswa Laporan Lama',
            'status' => 'active',
        ]);
        $matchingActivity = SchoolActivity::create([
            'student_id' => $matchingStudent->id,
            'teacher_id' => $teacher->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);
        $olderActivity = SchoolActivity::create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $teacher->teacher->id,
            'activity_date' => today()->subDays(7),
            'attendance' => 'present',
        ]);

        $this->actingAs($teacher);

        Livewire::test(ListSchoolActivities::class)
            ->assertTableFilterExists('student_id')
            ->assertTableFilterExists('class_id')
            ->assertTableFilterExists('attendance')
            ->assertTableFilterExists('activity_date')
            ->filterTable('student_id', $matchingStudent)
            ->assertCanSeeTableRecords([$matchingActivity])
            ->assertCanNotSeeTableRecords([$olderActivity])
            ->resetTableFilters()
            ->filterTable('activity_date', [
                'from' => today()->toDateString(),
                'until' => today()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$matchingActivity])
            ->assertCanNotSeeTableRecords([$olderActivity]);
    }

    public function test_teacher_home_activity_list_has_student_class_and_date_filters(): void
    {
        $this->actingAs(User::role('guru')->firstOrFail());

        Livewire::test(ListHomeActivities::class)
            ->assertTableFilterExists('student_id')
            ->assertTableFilterExists('class_id')
            ->assertTableFilterVisible('class_id')
            ->assertTableFilterExists('activity_date');
    }
}
