<?php

namespace Tests\Feature;

use App\Filament\Widgets\CurrentLessonSchedule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CurrentLessonScheduleWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow(now()->startOfWeek()->setTime(7, 15));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_teacher_student_and_parent_see_the_current_lesson_for_their_class(): void
    {
        foreach (['guru', 'siswa', 'orang_tua'] as $role) {
            $user = User::role($role)->firstOrFail();

            $this->actingAs($user);

            $widget = new class extends CurrentLessonSchedule
            {
                public function schedulesForTest()
                {
                    return $this->getCurrentSchedules();
                }
            };

            $schedules = $widget->schedulesForTest();

            $this->assertTrue(CurrentLessonSchedule::canView());
            $this->assertCount(1, $schedules);
            $this->assertSame('Matematika', $schedules->first()->teachingAssignment->subject->name);
            $this->assertSame('Kelas 1A', $schedules->first()->teachingAssignment->schoolClass->name);
        }
    }

    public function test_dashboard_renders_the_current_lesson_highlight_for_student(): void
    {
        $student = User::role('siswa')->firstOrFail();

        $this->actingAs($student)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Sedang berlangsung')
            ->assertSee('Berlangsung sekarang')
            ->assertSee('Matematika')
            ->assertSee('Kelas 1A');
    }

    public function test_widget_shows_no_current_lesson_after_the_lesson_period_ends(): void
    {
        Carbon::setTestNow(now()->startOfWeek()->setTime(7, 35));
        $student = User::role('siswa')->firstOrFail();

        $this->actingAs($student);

        $widget = new class extends CurrentLessonSchedule
        {
            public function schedulesForTest()
            {
                return $this->getCurrentSchedules();
            }
        };

        $this->assertEmpty($widget->schedulesForTest());
    }
}
