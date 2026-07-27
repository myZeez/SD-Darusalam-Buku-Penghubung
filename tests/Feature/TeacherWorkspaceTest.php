<?php

namespace Tests\Feature;

use App\Actions\Students\SaveStudentWithFamily;
use App\Filament\Pages\AccountSecurity;
use App\Filament\Pages\TeacherAttendance;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\Pages\CreateSchoolActivity;
use App\Filament\Resources\SchoolActivities\Pages\EditSchoolActivity;
use App\Filament\Resources\SchoolClasses\Pages\ViewSchoolClass;
use App\Filament\Resources\SchoolClasses\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Models\AttendanceRecord;
use App\Models\EarlyDeparture;
use App\Models\ParentProfile;
use App\Models\ParentSubmission;
use App\Models\Schedule;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_teacher_navigation_opens_an_editable_personal_profile(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $teacher = $guru->teacher;
        $otherTeacher = $this->createOtherClass()->teacher;

        $this->actingAs($guru);

        $this->assertFalse(TeacherResource::canCreate());
        $this->assertTrue(TeacherResource::canEdit($teacher));
        $this->assertFalse(TeacherResource::canView($otherTeacher));
        $this->assertFalse(TeacherResource::canEdit($otherTeacher));
        $this->assertStringContainsString("/admin/teachers/{$teacher->id}", TeacherResource::getNavigationUrl());

        $this->get(TeacherResource::getNavigationUrl())
            ->assertOk()
            ->assertSee('Profil Wali Kelas')
            ->assertSee($guru->name)
            ->assertSee($teacher->nip);

        $this->get(TeacherResource::getUrl('edit', ['record' => $teacher]))
            ->assertOk()
            ->assertSee('Foto Profil')
            ->assertSee('Nama Guru')
            ->assertSee('Data Kepegawaian');

        $this->get('/admin/teachers/create')->assertForbidden();

        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(StudentArrivalResource::canViewAny());
        $this->assertFalse(ExtracurricularResource::canViewAny());
        $this->get('/admin')
            ->assertDontSee('/admin/keamanan-akun', false)
            ->assertDontSee('/admin/student-arrivals', false)
            ->assertDontSee('/admin/extracurriculars', false);
        $this->get(StudentArrivalResource::getUrl())->assertForbidden();
        $this->get(ExtracurricularResource::getUrl())->assertForbidden();

        $this->get(TeacherResource::getUrl('view', ['record' => $otherTeacher]))
            ->assertNotFound();
    }

    public function test_teacher_class_workspace_uses_cards_and_contains_the_student_list(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $class = SchoolClass::query()->firstOrFail();
        $student = Student::query()->firstOrFail();
        $otherClass = $this->createOtherClass();

        $this->actingAs($guru);

        $this->assertTrue(StudentResource::shouldRegisterNavigation());
        $this->assertFalse(SchoolClassResource::canCreate());
        $this->assertTrue(SchoolClassResource::canEdit($class));
        $this->assertFalse(SchoolClassResource::canView($otherClass));
        $this->assertFalse(SchoolClassResource::canEdit($otherClass));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Kelas dan Siswa')
            ->assertSee('/admin/students', false);

        $this->get(SchoolClassResource::getUrl())
            ->assertOk()
            ->assertSee($class->name)
            ->assertSee('1/30 siswa')
            ->assertSee('Lihat Detail');

        $this->get(SchoolClassResource::getUrl('view', ['record' => $class]))
            ->assertOk()
            ->assertSee(StudentsRelationManager::class, false);

        $this->get(SchoolClassResource::getUrl('view', ['record' => $otherClass]))
            ->assertNotFound();

        Livewire::test(StudentsRelationManager::class, [
            'ownerRecord' => $class,
            'pageClass' => ViewSchoolClass::class,
        ])
            ->assertSee('Siswa di Kelas Ini')
            ->assertCanSeeTableRecords([$student]);
    }

    public function test_teacher_gets_a_validation_error_for_a_duplicate_daily_school_report(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $student = $guru->accessibleStudents()->firstOrFail();
        $existingReport = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $guru->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
            'note' => 'Laporan yang sudah tersimpan.',
        ]);

        $this->actingAs($guru);

        Livewire::test(CreateSchoolActivity::class)
            ->fillForm([
                'student_id' => $student->id,
                'activity_date' => today()->toDateString(),
                'attendance' => 'present',
                'note' => 'Data duplikat tidak boleh tersimpan.',
            ])
            ->call('create')
            ->assertHasFormErrors(['activity_date'])
            ->assertSee(SchoolActivity::DUPLICATE_DAILY_REPORT_MESSAGE);

        $this->assertDatabaseCount('school_activities', 1);
        $this->assertDatabaseMissing('school_activities', [
            'note' => 'Data duplikat tidak boleh tersimpan.',
        ]);

        Livewire::test(EditSchoolActivity::class, ['record' => $existingReport->getRouteKey()])
            ->fillForm(['note' => 'Laporan yang sudah tersimpan diperbarui.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('school_activities', [
            'id' => $existingReport->id,
            'note' => 'Laporan yang sudah tersimpan diperbarui.',
        ]);
    }

    public function test_full_class_automatically_places_a_new_student_in_an_available_class_of_the_same_grade(): void
    {
        $fullClass = SchoolClass::query()->firstOrFail();
        $fullClass->update(['capacity' => 1, 'grade_level' => 1]);

        $otherTeacherUser = User::factory()->create([
            'email' => 'guru-kedua@sddarusalam.test',
            'status' => 'active',
        ]);
        $otherTeacherUser->assignRole('guru');
        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'nip' => '197001012000011002',
        ]);
        $availableClass = SchoolClass::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Kelas 1B',
            'grade_level' => 1,
            'capacity' => 30,
        ]);
        $parent = ParentProfile::query()->firstOrFail();

        $student = app(SaveStudentWithFamily::class)->create([
            'class_id' => $fullClass->id,
            'nis' => 'SDD-AUTO-001',
            'name' => 'Siswa Penempatan Otomatis',
            'gender' => 'male',
            'birth_date' => '2018-03-12',
            'status' => 'active',
            'student_email' => 'siswa-auto@sddarusalam.test',
            'student_password' => 'password-siswa',
            'parent_mode' => 'existing',
            'parent_id' => $parent->id,
        ]);

        $this->assertSame($availableClass->id, $student->class_id);
        $this->assertSame(1, $fullClass->students()->count());
        $this->assertSame(1, $availableClass->students()->count());
    }

    public function test_teacher_can_manage_only_schedules_for_their_class(): void
    {
        $admin = User::role('admin')->where('job_title', 'Administrator Sekolah')->firstOrFail();
        $principal = User::role('admin')->where('job_title', 'Kepala Sekolah')->firstOrFail();
        $guru = User::role('guru')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $class = SchoolClass::query()->firstOrFail();
        $otherClass = $this->createOtherClass();

        $globalSchedule = Schedule::create([
            'created_by' => $admin->id,
            'title' => 'Agenda Umum',
            'activity_date' => today(),
        ]);
        $ownSchedule = Schedule::create([
            'class_id' => $class->id,
            'created_by' => $guru->id,
            'title' => 'Agenda Kelas Saya',
            'activity_date' => today(),
        ]);
        $otherSchedule = Schedule::create([
            'class_id' => $otherClass->id,
            'created_by' => $otherClass->teacher->user_id,
            'title' => 'Agenda Kelas Lain',
            'activity_date' => today(),
        ]);

        $this->actingAs($guru);
        $teacherScheduleIds = ScheduleResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($teacherScheduleIds->contains($globalSchedule->id));
        $this->assertTrue($teacherScheduleIds->contains($ownSchedule->id));
        $this->assertFalse($teacherScheduleIds->contains($otherSchedule->id));
        $this->assertTrue(ScheduleResource::canEdit($ownSchedule));
        $this->assertFalse(ScheduleResource::canEdit($globalSchedule));

        $this->get('/admin/schedules/create')
            ->assertOk()
            ->assertSee('Target Kelas')
            ->assertSee($class->name)
            ->assertDontSee($otherClass->name);

        $this->flushSession();
        $this->actingAs($principal);
        $this->assertTrue(ScheduleResource::canCreate());
        $this->get('/admin/schedules/create')
            ->assertOk()
            ->assertSee($class->name)
            ->assertSee($otherClass->name)
            ->assertSee('Kosongkan untuk membuat agenda umum bagi seluruh sekolah.');

        Livewire::test(CreateSchedule::class)
            ->fillForm([
                'class_id' => null,
                'title' => 'Agenda dari Kepala Sekolah',
                'activity_date' => today()->addDays(2)->toDateString(),
                'description' => 'Agenda berlaku untuk seluruh kelas.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schedules', [
            'class_id' => null,
            'created_by' => $principal->id,
            'title' => 'Agenda dari Kepala Sekolah',
        ]);

        $this->flushSession();
        $this->actingAs($orangTua);
        $parentScheduleIds = ScheduleResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($parentScheduleIds->contains($globalSchedule->id));
        $this->assertTrue($parentScheduleIds->contains($ownSchedule->id));
        $this->assertFalse($parentScheduleIds->contains($otherSchedule->id));
        $this->get(ScheduleResource::getUrl())
            ->assertOk()
            ->assertSee('schedule-card', false)
            ->assertSee('Agenda Umum')
            ->assertSee('Agenda Kelas Saya')
            ->assertDontSee('Agenda Kelas Lain');
    }

    public function test_teacher_can_notify_only_students_and_parents_from_their_class(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $orangTua = User::role('orang_tua')->firstOrFail();
        $student = Student::query()->firstOrFail();
        $otherStudentUser = User::factory()->create([
            'email' => 'siswa-luar-kelas@sddarusalam.test',
            'status' => 'active',
        ]);
        $otherStudentUser->assignRole('siswa');
        Student::create([
            'user_id' => $otherStudentUser->id,
            'nis' => 'SDD-OUTSIDE-001',
            'name' => 'Siswa Luar Kelas',
            'status' => 'active',
        ]);

        $this->actingAs($guru);

        $this->assertTrue(UserNotificationResource::canNotifyUser($student->user_id));
        $this->assertTrue(UserNotificationResource::canNotifyUser($orangTua->id));
        $this->assertFalse(UserNotificationResource::canNotifyUser($otherStudentUser->id));

        $this->get('/admin/user-notifications/create')
            ->assertOk()
            ->assertSee('Hanya siswa dan orang tua dari kelas yang Anda ampu.')
            ->assertSee($student->user->email)
            ->assertSee($orangTua->email)
            ->assertDontSee($otherStudentUser->email);

        $notification = UserNotification::create([
            'user_id' => $orangTua->id,
            'created_by' => $guru->id,
            'title' => 'Pesan untuk Orang Tua',
            'message' => 'Mohon memeriksa laporan harian siswa.',
        ]);

        $this->assertTrue(UserNotificationResource::canEdit($notification));
        $this->assertTrue(UserNotificationResource::getEloquentQuery()->whereKey($notification)->exists());

        $this->flushSession();

        $this->actingAs($orangTua)
            ->get(UserNotificationResource::getUrl('view', ['record' => $notification]))
            ->assertOk();

        $this->assertTrue($notification->refresh()->is_read);
    }

    public function test_teacher_daily_attendance_combines_gate_and_parent_data_and_saves_the_whole_class(): void
    {
        $guru = User::role('guru')->firstOrFail();
        $gate = User::role('loket')->firstOrFail();
        $parentUser = User::role('orang_tua')->firstOrFail();
        $class = SchoolClass::query()->firstOrFail();
        $firstStudent = Student::query()->firstOrFail();
        $secondStudent = $this->createStudentInClass($class, 'SDD-ATT-002', 'Siswa Sakit');
        $thirdStudent = $this->createStudentInClass($class, 'SDD-ATT-003', 'Siswa Belum Hadir');

        $this->actingAs($gate);
        StudentArrival::create([
            'student_id' => $firstStudent->id,
            'arrival_date' => today(),
            'arrival_time' => '07:30:00',
        ]);

        $this->actingAs($parentUser);
        $submission = ParentSubmission::create([
            'student_id' => $secondStudent->id,
            'type' => 'sick',
            'title' => 'Demam dan perlu istirahat',
            'description' => 'Anak beristirahat di rumah.',
            'start_date' => today(),
            'end_date' => today(),
            'status' => 'pending',
        ]);

        $teacherNotification = UserNotification::query()
            ->where('user_id', $guru->id)
            ->where('title', 'Laporan Orang Tua: '.$secondStudent->name)
            ->firstOrFail();

        $this->assertFalse($teacherNotification->is_read);

        $this->actingAs($guru);
        $this->get(UserNotificationResource::getUrl())
            ->assertOk()
            ->assertSee('Notifikasi Guru')
            ->assertSee('Masuk')
            ->assertSee($teacherNotification->title);

        $this->assertTrue(ParentSubmissionResource::canReview($submission));
        $this->assertFalse(ParentSubmissionResource::canEdit($submission));
        $this->get(ParentSubmissionResource::getUrl('view', ['record' => $submission]))
            ->assertOk()
            ->assertSee('Setujui')
            ->assertSee('Tolak')
            ->assertDontSee('Ubah');
        $this->get(ParentSubmissionResource::getUrl('edit', ['record' => $submission]))->assertForbidden();

        Livewire::test(TeacherAttendance::class)
            ->assertSee('Informasi dari Orang Tua')
            ->assertSee($secondStudent->name)
            ->assertSee($parentUser->name)
            ->assertSee('Anak beristirahat di rumah.')
            ->call('approveSubmission', $submission->id)
            ->assertHasNoErrors()
            ->assertDontSee('Informasi dari Orang Tua');

        $this->assertSame('approved', $submission->refresh()->status);

        $this->assertFalse(AttendanceRecordResource::shouldRegisterNavigation());
        $this->assertFalse(AttendanceRecordResource::canViewAny());
        $this->assertTrue(TeacherAttendance::canAccess());
        $this->assertFalse(ParentSubmissionResource::canCreate());
        $this->get(ParentSubmissionResource::getUrl('create'))->assertForbidden();
        $this->get(AttendanceRecordResource::getUrl())->assertForbidden();
        $this->get(AttendanceRecordResource::getUrl('create'))->assertForbidden();

        $this->get(TeacherAttendance::getUrl())
            ->assertOk()
            ->assertSee('Presensi Kelas')
            ->assertSee('Tandai Semua Hadir')
            ->assertSee($firstStudent->name)
            ->assertSee($secondStudent->name)
            ->assertSee($thirdStudent->name)
            ->assertSee('Dari Piket')
            ->assertSee('Laporan Orang Tua')
            ->assertSee('Terlambat');

        Livewire::test(TeacherAttendance::class)
            ->assertSet('selectedClassId', $class->id)
            ->assertSet('attendance.'.$firstStudent->id, 'present')
            ->assertSet('attendance.'.$secondStudent->id, 'sick')
            ->assertSet('attendance.'.$thirdStudent->id, null)
            ->call('markAllPresent')
            ->set('attendance.'.$thirdStudent->id, 'absent')
            ->set('notes.'.$thirdStudent->id, 'Tanpa keterangan.')
            ->call('saveAttendance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $firstStudent->id,
            'status' => 'present',
            'source' => 'arrival',
            'is_late' => true,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $secondStudent->id,
            'status' => 'sick',
            'source' => 'parent_submission',
            'notes' => 'Anak beristirahat di rumah.',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $thirdStudent->id,
            'recorded_by' => $guru->id,
            'status' => 'absent',
            'source' => 'teacher',
            'notes' => 'Tanpa keterangan.',
        ]);

        $this->assertTrue($teacherNotification->fresh()->is_read);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $parentUser->id,
            'created_by' => $guru->id,
            'title' => 'Pengajuan Disetujui: '.$submission->title,
            'is_read' => false,
        ]);

        $this->get(route('reports.class-attendance', [
            'class_id' => $class->id,
            'date' => today()->toDateString(),
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $otherClass = $this->createOtherClass();
        $this->get(route('reports.class-attendance', [
            'class_id' => $otherClass->id,
            'date' => today()->toDateString(),
        ]))->assertForbidden();

        $this->flushSession();
        $this->actingAs(User::role('admin')->firstOrFail());
        $this->assertTrue(AttendanceRecordResource::shouldRegisterNavigation());
        $this->assertFalse(TeacherAttendance::canAccess());
        $this->get(TeacherAttendance::getUrl())->assertForbidden();
    }

    public function test_teacher_attendance_calendar_marks_complete_and_incomplete_dates_and_selects_a_date(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $class = $teacher->managedClasses()->firstOrFail();
        $students = Student::query()->where('class_id', $class->id)->where('status', 'active')->get();

        if ($students->isEmpty()) {
            $students->push(Student::create([
                'class_id' => $class->id,
                'parent_id' => ParentProfile::query()->firstOrFail()->id,
                'nis' => 'SDD-CALENDAR-001',
                'name' => 'Siswa Kalender',
                'status' => 'active',
            ]));
        }
        $completeDate = today()->toImmutable()->subMonthNoOverflow()->startOfMonth()->addDays(2);
        $incompleteDate = $completeDate->addDay();

        foreach ($students as $student) {
            AttendanceRecord::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'recorded_by' => $teacher->id,
                'attendance_date' => $completeDate,
                'status' => 'present',
                'source' => 'teacher',
            ]);
        }

        $this->actingAs($teacher);

        $page = Livewire::test(TeacherAttendance::class)
            ->set('selectedClassId', $class->id)
            ->set('calendarMonth', $completeDate->startOfMonth()->toDateString());

        $calendarDays = collect($page->instance()->calendarDays())->keyBy('date');

        $this->assertTrue($calendarDays[$completeDate->toDateString()]['is_complete']);
        $this->assertFalse($calendarDays[$completeDate->toDateString()]['is_incomplete']);
        $this->assertFalse($calendarDays[$incompleteDate->toDateString()]['is_complete']);
        $this->assertTrue($calendarDays[$incompleteDate->toDateString()]['is_incomplete']);

        $page
            ->call('selectCalendarDate', $completeDate->toDateString())
            ->assertSet('selectedDate', $completeDate->toDateString())
            ->assertSee('Kalender Presensi');
    }

    public function test_approved_early_leave_request_creates_a_early_departure_record(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $teacher->managedStudents()->firstOrFail();

        $this->actingAs($parent);

        $submission = ParentSubmission::create([
            'student_id' => $student->id,
            'type' => 'early_leave',
            'title' => 'Izin Pulang Cepat - '.$student->name,
            'description' => 'Ada jadwal pemeriksaan kesehatan.',
            'start_date' => today(),
            'early_leave_time' => '11:30:00',
            'status' => 'pending',
        ]);

        $this->actingAs($teacher);
        $submission->update(['status' => 'approved']);

        $departure = EarlyDeparture::query()
            ->where('student_id', $student->id)
            ->where('parent_submission_id', $submission->id)
            ->firstOrFail();

        $this->assertTrue($departure->departure_date->isSameDay(today()));
        $this->assertSame('11:30:00', $departure->departure_time);
        $this->assertSame('Ada jadwal pemeriksaan kesehatan.', $departure->notes);
    }

    private function createOtherClass(): SchoolClass
    {
        $teacherUser = User::factory()->create([
            'email' => 'guru-lain@sddarusalam.test',
            'status' => 'active',
        ]);
        $teacherUser->assignRole('guru');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '197001012000011003',
        ]);

        return SchoolClass::create([
            'teacher_id' => $teacher->id,
            'name' => 'Kelas 2A',
            'grade_level' => 2,
            'capacity' => 30,
        ]);
    }

    private function createStudentInClass(SchoolClass $class, string $nis, string $name): Student
    {
        return Student::create([
            'class_id' => $class->id,
            'parent_id' => ParentProfile::query()->firstOrFail()->id,
            'nis' => $nis,
            'name' => $name,
            'status' => 'active',
        ]);
    }
}
