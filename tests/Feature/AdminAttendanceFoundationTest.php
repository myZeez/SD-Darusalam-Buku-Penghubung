<?php

namespace Tests\Feature;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use App\Filament\Resources\StudentArrivals\Pages\LoketArrival;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\AttendanceRecord;
use App\Models\ParentProfile;
use App\Models\ParentSubmission;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAttendanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_accounts_share_access_while_gate_remains_focused(): void
    {
        $admin = User::role('admin')->where('job_title', 'Administrator Sekolah')->firstOrFail();
        $principal = User::role('admin')->where('job_title', 'Kepala Sekolah')->firstOrFail();
        $gate = User::role('loket')->firstOrFail();
        $setting = SchoolSetting::current();
        $student = Student::query()->firstOrFail();
        $submission = ParentSubmission::create([
            'student_id' => $student->id,
            'type' => 'sick',
            'title' => 'Demam',
            'description' => 'Anak perlu beristirahat.',
            'start_date' => today(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $this->assertTrue(SchoolSettingResource::canEdit($setting));
        $this->assertTrue(StudentArrivalResource::canCreate());
        $this->assertTrue(StudentResource::canViewAny());
        $this->assertTrue(AttendanceRecordResource::canCreate());
        $this->assertTrue(ParentSubmissionResource::canViewAny());
        $this->assertFalse(ParentSubmissionResource::canCreate());
        $this->assertFalse(ParentSubmissionResource::canEdit($submission));
        $this->assertFalse(ParentSubmissionResource::canReview($submission));

        $this->get(SchoolSettingResource::getUrl('edit', ['record' => $setting]))->assertOk();
        $this->get(StudentArrivalResource::getUrl('create'))->assertOk();
        $this->get(AttendanceRecordResource::getUrl('create'))->assertOk();
        $this->get(ParentSubmissionResource::getUrl())->assertOk();

        $this->flushSession();
        $this->actingAs($principal);
        $this->assertTrue(SchoolSettingResource::canView($setting));
        $this->assertTrue(SchoolSettingResource::canEdit($setting));
        $this->assertTrue(StudentArrivalResource::canViewAny());
        $this->assertTrue(StudentArrivalResource::canCreate());
        $this->assertTrue(AttendanceRecordResource::canViewAny());
        $this->assertTrue(AttendanceRecordResource::canCreate());
        $this->assertTrue(ParentSubmissionResource::canViewAny());
        $this->assertFalse(ParentSubmissionResource::canCreate());
        $this->assertFalse(ParentSubmissionResource::canEdit($submission));
        $this->assertFalse(ParentSubmissionResource::canReview($submission));

        $this->get(SchoolSettingResource::getUrl('edit', ['record' => $setting]))->assertOk();
        $this->get(StudentArrivalResource::getUrl())->assertOk();
        $this->get(AttendanceRecordResource::getUrl())->assertOk();
        $this->get(ParentSubmissionResource::getUrl())->assertOk();

        $this->flushSession();
        $this->actingAs($gate);
        $this->assertTrue(StudentArrivalResource::canCreate());
        $this->assertFalse(StudentResource::canViewAny());
        $this->assertFalse(AttendanceRecordResource::canViewAny());
        $this->assertFalse(ParentSubmissionResource::canViewAny());
        $this->assertFalse(SchoolSettingResource::canViewAny());

        $this->get(StudentArrivalResource::getUrl('create'))->assertOk();
        $this->get(StudentResource::getUrl())->assertForbidden();
        $this->get(AttendanceRecordResource::getUrl())->assertForbidden();
        $this->get(ParentSubmissionResource::getUrl())->assertForbidden();
        $this->get(SchoolSettingResource::getUrl())->assertForbidden();
    }

    public function test_gate_check_in_automatically_sets_class_attendance_lateness_and_teacher_notification(): void
    {
        $gate = User::role('loket')->firstOrFail();
        $student = Student::query()->with('class.teacher.user')->firstOrFail();
        $teacherUser = $student->class->teacher->user;
        $setting = SchoolSetting::current();
        $setting->update([
            'school_start_time' => '07:00:00',
            'late_tolerance_minutes' => 5,
        ]);

        $this->actingAs($gate);

        $arrival = StudentArrival::create([
            'student_id' => $student->id,
            'class_id' => null,
            'arrival_date' => today(),
            'arrival_time' => '07:06:00',
        ]);

        $this->assertSame($student->class_id, $arrival->class_id);
        $this->assertSame($gate->id, $arrival->recorded_by);
        $this->assertSame('late', $arrival->status);

        $attendance = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', today())
            ->firstOrFail();

        $this->assertSame('present', $attendance->status);
        $this->assertTrue($attendance->is_late);
        $this->assertSame('arrival', $attendance->source);
        $this->assertSame($arrival->id, $attendance->arrival_id);

        $notification = UserNotification::query()
            ->where('user_id', $teacherUser->id)
            ->where('title', 'like', 'Siswa Terlambat:%')
            ->firstOrFail();

        $this->assertStringContainsString($student->name, $notification->title);

        $arrival->update(['arrival_time' => '06:55:00']);
        $attendance->refresh();

        $this->assertSame('on_time', $arrival->fresh()->status);
        $this->assertFalse($attendance->is_late);
        $this->assertFalse(UserNotification::query()->whereKey($notification)->exists());
    }

    public function test_gate_account_has_a_focused_workspace_and_can_record_today_arrival(): void
    {
        $gate = User::role('loket')->firstOrFail();
        $student = Student::query()->firstOrFail();
        SchoolSetting::current()->update([
            'school_start_time' => '07:00:00',
            'late_tolerance_minutes' => 5,
        ]);

        $this->actingAs($gate);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Catat Kedatangan')
            ->assertSee(StudentArrivalResource::getUrl('desk'), false)
            ->assertDontSee('/admin/students', false)
            ->assertDontSee('/admin/school-classes', false)
            ->assertDontSee('/admin/attendance-records', false)
            ->assertDontSee('/admin/parent-submissions', false)
            ->assertDontSee('/admin/users', false);

        Livewire::test(LoketArrival::class)
            ->assertSee($student->name)
            ->call('markPresent', $student->id);

        $arrival = StudentArrival::query()->latest('id')->firstOrFail();

        $this->assertSame($student->id, $arrival->student_id);
        $this->assertSame($student->class_id, $arrival->class_id);
        $this->assertSame($gate->id, $arrival->recorded_by);
        $this->assertTrue($arrival->arrival_date->isToday());
        $this->assertContains($arrival->status, ['on_time', 'late']);
        $this->assertTrue(StudentArrivalResource::canEdit($arrival));

        $otherOfficerArrival = $arrival->replicate();
        $otherOfficerArrival->recorded_by = User::role('admin')->firstOrFail()->id;

        $previousDayArrival = $arrival->replicate();
        $previousDayArrival->recorded_by = $gate->id;
        $previousDayArrival->arrival_date = today()->subDay();

        $this->assertFalse(StudentArrivalResource::canEdit($otherOfficerArrival));
        $this->assertFalse(StudentArrivalResource::canEdit($previousDayArrival));
    }

    public function test_approved_parent_sick_report_is_linked_and_creates_attendance_records(): void
    {
        $reviewer = User::role('guru')->firstOrFail();
        $student = Student::query()->firstOrFail();
        $wrongParent = ParentProfile::create([
            'user_id' => User::factory()->create([
                'email' => 'orang-tua-salah@sddarusalam.test',
                'status' => 'active',
            ])->id,
        ]);
        $startDate = today()->addDays(3);
        $endDate = today()->addDays(4);

        $this->actingAs($reviewer);

        $submission = ParentSubmission::create([
            'student_id' => $student->id,
            'parent_id' => $wrongParent->id,
            'type' => 'sick',
            'title' => 'Sakit demam',
            'description' => 'Anak perlu istirahat di rumah.',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'approved',
        ]);

        $this->assertSame($student->parent_id, $submission->parent_id);
        $this->assertSame($reviewer->id, $submission->reviewed_by);
        $this->assertNotNull($submission->reviewed_at);

        $records = AttendanceRecord::query()
            ->where('parent_submission_id', $submission->id)
            ->orderBy('attendance_date')
            ->get();

        $this->assertCount(2, $records);
        $this->assertSame(['sick'], $records->pluck('status')->unique()->values()->all());
        $this->assertSame(['parent_submission'], $records->pluck('source')->unique()->values()->all());
        $this->assertSame(
            [$startDate->toDateString(), $endDate->toDateString()],
            $records->pluck('attendance_date')->map->toDateString()->all(),
        );
    }
}
