<?php

namespace Tests\Feature;

use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\ActivityComment;
use App\Models\AttendanceRecord;
use App\Models\Extracurricular;
use App\Models\HomeActivity;
use App\Models\LessonSchedule;
use App\Models\PasswordResetRequest;
use App\Models\Schedule;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_complete_connected_and_idempotent_data(): void
    {
        $this->seed(DemoDataSeeder::class);

        $demoStudents = Student::query()->where('nis', 'like', 'SDD-%');

        $this->assertSame(50, $demoStudents->count());
        $this->assertSame(50, (clone $demoStudents)->whereNotNull('user_id')->count());
        $this->assertSame(50, (clone $demoStudents)->whereNotNull('parent_id')->count());
        $this->assertSame(50, (clone $demoStudents)->whereNotNull('class_id')->count());
        $this->assertSame(10, Teacher::query()->whereHas('user', fn ($query) => $query
            ->where('email', 'like', 'guru%@sddarusalam.test'))->count());
        $demoTeachers = Teacher::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'like', 'guru%@sddarusalam.test'))
            ->withCount(['classes', 'assistedClasses'])
            ->get();
        $this->assertTrue($demoTeachers->every(
            fn (Teacher $teacher): bool => ($teacher->classes_count + $teacher->assisted_classes_count) === 1,
        ));

        foreach (['Kelas 1A', 'Kelas 1B', 'Kelas 2A', 'Kelas 2B', 'Kelas 3A'] as $className) {
            $class = SchoolClass::query()->where('name', $className)->firstOrFail();

            $this->assertNotNull($class->teacher_id);
            $this->assertNotNull($class->assistant_teacher_id);
            $this->assertSame(10, $class->students()->count());
        }

        $budi = User::query()->where('email', 'guru@sddarusalam.test')->firstOrFail();
        $ahmad = User::query()->where('email', 'guru3@sddarusalam.test')->firstOrFail();
        $class1A = SchoolClass::query()->where('name', 'Kelas 1A')->firstOrFail();

        $this->assertSame($budi->teacher->id, $class1A->teacher_id);
        $this->assertSame($ahmad->teacher->id, $class1A->assistant_teacher_id);

        $this->assertSame(500, AttendanceRecord::query()->count());
        $this->assertSame(250, SchoolActivity::query()->count());
        $this->assertSame(200, HomeActivity::query()->count());
        $this->assertSame(35, TeachingAssignment::query()->count());
        $this->assertSame(125, LessonSchedule::query()->count());
        $this->assertSame(0, TeachingAssignment::query()->doesntHave('lessonSchedules')->count());
        $this->assertSame(7, TeachingAssignment::query()
            ->whereHas('teacher.user', fn ($query) => $query->where('email', 'guru@sddarusalam.test'))
            ->count());
        $this->assertSame(25, LessonSchedule::query()
            ->whereHas('teachingAssignment.teacher.user', fn ($query) => $query->where('email', 'guru@sddarusalam.test'))
            ->count());

        $this->actingAs($ahmad);
        $this->assertSame(7, TeachingAssignmentResource::getEloquentQuery()->count());
        $this->assertSame(25, LessonScheduleResource::getEloquentQuery()->count());
        $this->assertSame(25, ActivityComment::query()->whereNull('parent_id')->count());
        $this->assertSame(8, Schedule::query()->count());
        $this->assertSame(4, Extracurricular::query()->count());
        $this->assertSame(4, PasswordResetRequest::query()->where('status', 'pending')->count());

        $countsBeforeSecondRun = $this->demoCounts();
        $this->seed(DemoDataSeeder::class);

        $this->assertSame($countsBeforeSecondRun, $this->demoCounts());
    }

    /** @return array<string, int> */
    private function demoCounts(): array
    {
        return [
            'students' => Student::query()->where('nis', 'like', 'SDD-%')->count(),
            'attendances' => AttendanceRecord::query()->count(),
            'school_activities' => SchoolActivity::query()->count(),
            'home_activities' => HomeActivity::query()->count(),
            'teaching_assignments' => TeachingAssignment::query()->count(),
            'lesson_schedules' => LessonSchedule::query()->count(),
            'comments' => ActivityComment::query()->count(),
            'schedules' => Schedule::query()->count(),
            'extracurriculars' => Extracurricular::query()->count(),
            'password_reset_requests' => PasswordResetRequest::query()->count(),
        ];
    }
}
