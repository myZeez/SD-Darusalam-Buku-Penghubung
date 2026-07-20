<?php

namespace Tests\Feature;

use App\Filament\Pages\TeacherAttendance;
use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use App\Filament\Resources\LessonPeriods\Pages\CreateLessonPeriod;
use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\TeachingAssignments\Pages\CreateTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\AcademicPeriod;
use App\Models\LessonPeriod;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_academic_resources_follow_the_role_matrix(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $teacher = User::role('guru')->firstOrFail();
        $parent = User::role('orang_tua')->firstOrFail();
        $student = User::role('siswa')->firstOrFail();
        $gate = User::role('loket')->firstOrFail();

        $this->actingAs($admin);
        foreach ([
            AcademicPeriodResource::class,
            SubjectResource::class,
            TeachingAssignmentResource::class,
            LessonPeriodResource::class,
            LessonScheduleResource::class,
        ] as $resource) {
            $this->assertTrue($resource::canViewAny());
            $this->assertTrue($resource::canCreate());
        }

        $this->get(AcademicPeriodResource::getUrl())->assertOk();
        $this->get(SubjectResource::getUrl())->assertOk();
        $this->get(TeachingAssignmentResource::getUrl())->assertOk();
        $this->get(LessonPeriodResource::getUrl())->assertOk();
        $this->get(LessonScheduleResource::getUrl())->assertOk();

        $this->actingAs($teacher);
        $this->assertFalse(AcademicPeriodResource::canViewAny());
        $this->assertFalse(SubjectResource::canViewAny());
        $this->assertTrue(TeachingAssignmentResource::canViewAny());
        $this->assertFalse(TeachingAssignmentResource::canCreate());
        $this->assertFalse(LessonPeriodResource::canViewAny());
        $this->assertTrue(LessonScheduleResource::canViewAny());
        $this->assertFalse(LessonScheduleResource::canCreate());

        $this->actingAs($parent);
        $this->assertFalse(TeachingAssignmentResource::canViewAny());
        $this->assertTrue(LessonScheduleResource::canViewAny());
        $this->assertFalse(LessonScheduleResource::canCreate());

        foreach ([$student, $gate] as $blockedUser) {
            $this->flushSession();
            $this->actingAs($blockedUser);
            $this->assertFalse(TeachingAssignmentResource::canViewAny());
            $this->assertFalse(LessonScheduleResource::canViewAny());
            $this->get(LessonScheduleResource::getUrl())->assertForbidden();
        }
    }

    public function test_subject_teacher_scope_does_not_grant_homeroom_operations(): void
    {
        $class = SchoolClass::query()->firstOrFail();
        $period = AcademicPeriod::query()->where('is_active', true)->firstOrFail();
        $subjectTeacher = $this->createTeacher('guru.mapel@sddarusalam.test', 'Guru Mata Pelajaran');
        $subject = Subject::create([
            'code' => 'BIG',
            'name' => 'Bahasa Inggris',
            'is_active' => true,
        ]);
        $assignment = TeachingAssignment::create([
            'academic_period_id' => $period->id,
            'teacher_id' => $subjectTeacher->teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);
        $secondPeriod = LessonPeriod::create([
            'academic_period_id' => $period->id,
            'name' => 'Jam Pelajaran 2',
            'sequence' => 2,
            'start_time' => '07:35:00',
            'end_time' => '08:10:00',
            'type' => 'lesson',
            'is_active' => true,
        ]);
        $ownSchedule = LessonSchedule::create([
            'teaching_assignment_id' => $assignment->id,
            'lesson_period_id' => $secondPeriod->id,
            'day_of_week' => 'monday',
            'room' => $class->room,
            'is_active' => true,
        ]);

        $this->actingAs($subjectTeacher);

        $this->assertTrue($subjectTeacher->accessibleClasses()->whereKey($class)->exists());
        $this->assertFalse($subjectTeacher->managedClasses()->whereKey($class)->exists());
        $this->assertTrue($subjectTeacher->accessibleStudents()->exists());
        $this->assertFalse(TeacherAttendance::canAccess());
        $this->assertFalse(ScheduleResource::canCreate());
        $this->assertTrue(SchoolClassResource::canView($class));
        $this->assertFalse(SchoolClassResource::canEdit($class));
        $this->assertSame([], ParentSubmissionResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$assignment->id], TeachingAssignmentResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$ownSchedule->id], LessonScheduleResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_admin_can_assign_one_teacher_to_many_subjects_at_once(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $period = AcademicPeriod::query()->where('is_active', true)->firstOrFail();
        $class = SchoolClass::query()->where('academic_period_id', $period->id)->firstOrFail();
        $teacher = Teacher::query()->firstOrFail();
        $existingSubject = Subject::query()->firstOrFail();
        $additionalSubjects = collect([
            ['code' => 'BID', 'name' => 'Bahasa Indonesia'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam'],
        ])->map(fn (array $subject): Subject => Subject::create([
            ...$subject,
            'is_active' => true,
        ]));

        $this->actingAs($admin);

        Livewire::test(CreateTeachingAssignment::class)
            ->assertSee('Pilih semua')
            ->fillForm([
                'academic_period_id' => $period->id,
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_ids' => [
                    $existingSubject->id,
                    ...$additionalSubjects->pluck('id')->all(),
                ],
                'is_active' => true,
                'notes' => 'Guru kelas mengampu seluruh mata pelajaran.',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(TeachingAssignmentResource::getUrl());

        $assignments = TeachingAssignment::query()
            ->where('academic_period_id', $period->id)
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $class->id)
            ->whereIn('subject_id', [
                $existingSubject->id,
                ...$additionalSubjects->pluck('id')->all(),
            ])
            ->get();

        $this->assertCount(3, $assignments);
        $this->assertTrue($assignments->every->is_active);
        $this->assertSame(
            ['BID', 'IPA', $existingSubject->code],
            $assignments->load('subject')->pluck('subject.code')->sort()->values()->all(),
        );
    }

    public function test_schedule_rejects_class_teacher_and_room_conflicts(): void
    {
        $period = AcademicPeriod::query()->where('is_active', true)->firstOrFail();
        $lessonPeriod = LessonPeriod::query()->firstOrFail();
        $existingAssignment = TeachingAssignment::query()->firstOrFail();
        $existingSchedule = LessonSchedule::query()->firstOrFail();
        $existingSchedule->update(['room' => 'Ruang 1A']);

        $secondTeacher = $this->createTeacher('guru.kedua@sddarusalam.test', 'Guru Kedua');
        $secondSubject = Subject::create(['code' => 'BID', 'name' => 'Bahasa Indonesia', 'is_active' => true]);
        $sameClassAssignment = TeachingAssignment::create([
            'academic_period_id' => $period->id,
            'teacher_id' => $secondTeacher->teacher->id,
            'class_id' => $existingAssignment->class_id,
            'subject_id' => $secondSubject->id,
            'is_active' => true,
        ]);

        $this->assertValidationError(fn () => LessonSchedule::create([
            'teaching_assignment_id' => $sameClassAssignment->id,
            'lesson_period_id' => $lessonPeriod->id,
            'day_of_week' => 'monday',
            'is_active' => true,
        ]), 'teaching_assignment_id');

        $secondClass = SchoolClass::create([
            'teacher_id' => $secondTeacher->teacher->id,
            'academic_period_id' => $period->id,
            'name' => 'Kelas 2A',
            'grade_level' => 2,
            'academic_year' => $period->academic_year,
            'capacity' => 30,
            'room' => 'Ruang 2A',
        ]);
        $sameTeacherAssignment = TeachingAssignment::create([
            'academic_period_id' => $period->id,
            'teacher_id' => $existingAssignment->teacher_id,
            'class_id' => $secondClass->id,
            'subject_id' => $existingAssignment->subject_id,
            'is_active' => true,
        ]);

        $this->assertValidationError(fn () => LessonSchedule::create([
            'teaching_assignment_id' => $sameTeacherAssignment->id,
            'lesson_period_id' => $lessonPeriod->id,
            'day_of_week' => 'monday',
            'is_active' => true,
        ]), 'teaching_assignment_id');

        $thirdTeacher = $this->createTeacher('guru.ketiga@sddarusalam.test', 'Guru Ketiga');
        $thirdClass = SchoolClass::create([
            'teacher_id' => $thirdTeacher->teacher->id,
            'academic_period_id' => $period->id,
            'name' => 'Kelas 3A',
            'grade_level' => 3,
            'academic_year' => $period->academic_year,
            'capacity' => 30,
            'room' => 'Ruang 3A',
        ]);
        $roomAssignment = TeachingAssignment::create([
            'academic_period_id' => $period->id,
            'teacher_id' => $thirdTeacher->teacher->id,
            'class_id' => $thirdClass->id,
            'subject_id' => $secondSubject->id,
            'is_active' => true,
        ]);

        $this->assertValidationError(fn () => LessonSchedule::create([
            'teaching_assignment_id' => $roomAssignment->id,
            'lesson_period_id' => $lessonPeriod->id,
            'day_of_week' => 'monday',
            'room' => 'Ruang 1A',
            'is_active' => true,
        ]), 'room');
    }

    public function test_lesson_periods_cannot_overlap(): void
    {
        $academicPeriod = AcademicPeriod::query()->firstOrFail();

        $this->assertValidationError(fn () => LessonPeriod::create([
            'academic_period_id' => $academicPeriod->id,
            'name' => 'Periode Tumpang Tindih',
            'sequence' => 9,
            'start_time' => '07:20:00',
            'end_time' => '07:50:00',
            'type' => 'lesson',
            'is_active' => true,
        ]), 'start_time');
    }

    public function test_admin_can_create_a_complete_school_timeline_in_one_step(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $academicPeriod = AcademicPeriod::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(CreateLessonPeriod::class)
            ->assertSee('Tambah Waktu Berikutnya')
            ->assertSee('Mata pelajaran seperti Matematika atau IPS')
            ->fillForm([
                'academic_period_id' => $academicPeriod->id,
                'periods' => [
                    [
                        'type' => 'lesson',
                        'start_time' => '08:00',
                        'end_time' => '09:00',
                    ],
                    [
                        'type' => 'break',
                        'start_time' => '09:00',
                        'end_time' => '09:30',
                    ],
                    [
                        'type' => 'lesson',
                        'start_time' => '09:30',
                        'end_time' => '10:30',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(LessonPeriodResource::getUrl());

        $createdPeriods = LessonPeriod::query()
            ->where('academic_period_id', $academicPeriod->id)
            ->where('sequence', '>', 1)
            ->orderBy('sequence')
            ->get();

        $this->assertSame(
            ['Jam Pelajaran 2', 'Istirahat', 'Jam Pelajaran 3'],
            $createdPeriods->pluck('name')->all(),
        );
        $this->assertSame(
            ['08:00', '09:00', '09:30'],
            $createdPeriods->pluck('start_time')->all(),
        );
        $this->assertSame([2, 3, 4], $createdPeriods->pluck('sequence')->all());
    }

    private function createTeacher(string $email, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
        $user->assignRole('guru');
        $user->setRelation('teacher', Teacher::create([
            'user_id' => $user->id,
            'nip' => 'NIP-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
        ]));

        return $user;
    }

    private function assertValidationError(callable $callback, string $key): void
    {
        try {
            $callback();
            $this->fail("Validasi {$key} seharusnya gagal.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }
}
