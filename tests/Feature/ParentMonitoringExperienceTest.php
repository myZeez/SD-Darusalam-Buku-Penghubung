<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSecurity;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\ActivityComments\Pages\ViewActivityComment;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Models\ActivityComment;
use App\Models\AttendanceRecord;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularEnrollment;
use App\Models\ExtracurricularSession;
use App\Models\ParentProfile;
use App\Models\Schedule;
use App\Models\SchoolActivity;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParentMonitoringExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_parent_attendance_only_shows_the_requested_summary_fields(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $admin = User::role('admin')->firstOrFail();

        $attendance = AttendanceRecord::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'recorded_by' => $admin->id,
            'attendance_date' => today(),
            'status' => 'present',
            'is_late' => true,
            'source' => 'manual',
            'notes' => 'CATATAN-INTERNAL-TIDAK-BOLEH-TAMPIL',
        ]);

        $this->actingAs($parent);

        $this->get(AttendanceRecordResource::getUrl())
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($student->class->name)
            ->assertSee('Kehadiran')
            ->assertSee('Terlambat')
            ->assertDontSee($student->nis)
            ->assertDontSee('CATATAN-INTERNAL-TIDAK-BOLEH-TAMPIL');

        $this->get(AttendanceRecordResource::getUrl('view', ['record' => $attendance]))
            ->assertOk()
            ->assertSee($student->name)
            ->assertDontSee('Sumber Data')
            ->assertDontSee('Waktu Konfirmasi')
            ->assertDontSee('CATATAN-INTERNAL-TIDAK-BOLEH-TAMPIL');
    }

    public function test_parent_extracurricular_monitoring_never_exposes_other_students(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $teacher = Teacher::query()->firstOrFail();
        $otherStudent = Student::create([
            'nis' => 'NIS-RAHASIA-999',
            'name' => 'Murid Keluarga Lain',
            'status' => 'active',
        ]);
        $extracurricular = Extracurricular::create([
            'teacher_id' => $teacher->id,
            'name' => 'Pramuka Pemantauan',
            'day_of_week' => 'friday',
            'capacity' => 30,
            'status' => 'active',
        ]);

        foreach ([$student, $otherStudent] as $participant) {
            ExtracurricularEnrollment::create([
                'extracurricular_id' => $extracurricular->id,
                'student_id' => $participant->id,
                'status' => 'active',
            ]);
        }

        $session = ExtracurricularSession::create([
            'extracurricular_id' => $extracurricular->id,
            'teacher_id' => $teacher->id,
            'session_date' => today(),
            'title' => 'Latihan Mingguan',
            'coach_attendance_status' => 'present',
        ]);
        ExtracurricularAttendance::query()
            ->where('extracurricular_session_id', $session->id)
            ->where('student_id', $otherStudent->id)
            ->update(['status' => 'absent']);

        $this->actingAs($parent);

        $this->assertSame([], ExtracurricularResource::getRelations());

        $this->get(ExtracurricularResource::getUrl())
            ->assertOk()
            ->assertSee('Pramuka Pemantauan')
            ->assertSee($student->name)
            ->assertSee('1 dari 1 pertemuan hadir')
            ->assertDontSee($otherStudent->name)
            ->assertDontSee($otherStudent->nis);

        $this->get(ExtracurricularResource::getUrl('view', ['record' => $extracurricular]))
            ->assertOk()
            ->assertSee('Pemantauan Anak')
            ->assertSee('Hadir 1 dari 1 pertemuan')
            ->assertDontSee('Peserta Ekstrakurikuler')
            ->assertDontSee($otherStudent->name);
    }

    public function test_parent_school_report_is_read_only_and_scoped_to_their_children(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $teacher = Teacher::query()->firstOrFail();
        $schoolActivity = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
            'note' => 'Perkembangan belajar anak hari ini baik.',
        ]);
        $otherStudent = Student::create([
            'nis' => 'SDD-LAPORAN-RAHASIA-001',
            'name' => 'Siswa Keluarga Lain',
            'status' => 'active',
        ]);
        $otherActivity = SchoolActivity::create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
            'note' => 'Laporan keluarga lain tidak boleh terlihat.',
        ]);

        $this->actingAs($parent);

        $this->assertTrue(SchoolActivityResource::canViewAny());
        $this->assertTrue(SchoolActivityResource::canView($schoolActivity));
        $this->assertFalse(SchoolActivityResource::canView($otherActivity));
        $this->assertFalse(SchoolActivityResource::canCreate());
        $this->assertFalse(SchoolActivityResource::canEdit($schoolActivity));
        $this->assertFalse(SchoolActivityResource::canDelete($schoolActivity));
        $this->assertFalse(AccountSecurity::shouldRegisterNavigation());
        $this->assertFalse(UserNotificationResource::shouldRegisterNavigation());

        $this->get(SchoolActivityResource::getUrl())
            ->assertOk()
            ->assertSee($student->name)
            ->assertDontSee($otherStudent->name);

        $this->get(SchoolActivityResource::getUrl('view', ['record' => $schoolActivity]))
            ->assertOk()
            ->assertSee('Perkembangan belajar anak hari ini baik.')
            ->assertDontSee(SchoolActivityResource::getUrl('edit', ['record' => $schoolActivity]), false);

        $this->get(SchoolActivityResource::getUrl('view', ['record' => $otherActivity]))->assertNotFound();
        $this->get(SchoolActivityResource::getUrl('create'))->assertForbidden();
        $this->get(SchoolActivityResource::getUrl('edit', ['record' => $schoolActivity]))->assertForbidden();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Buka notifikasi')
            ->assertSee(UserNotificationResource::getUrl(), false)
            ->assertSee('Laporan Sekolah')
            ->assertDontSee('Keamanan Akun');
    }

    public function test_parent_can_reply_in_one_family_chat_thread(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $teacherUser = User::role('guru')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $activity = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherUser->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);
        $thread = ActivityComment::create([
            'activity_type' => SchoolActivity::class,
            'activity_id' => $activity->id,
            'user_id' => $teacherUser->id,
            'comment' => 'Pesan awal dari guru.',
        ]);

        $this->actingAs($parent);
        $reply = ActivityCommentResource::replyTo($thread, 'Balasan orang tua dalam utas yang sama.');

        $this->assertSame($thread->id, $reply->parent_id);
        $this->assertSame($thread->activity_type, $reply->activity_type);
        $this->assertSame($thread->activity_id, $reply->activity_id);
        $this->assertSame($parent->id, $reply->user_id);

        Livewire::test(ViewActivityComment::class, ['record' => $thread->getRouteKey()])
            ->call('selectReply', $thread->getKey())
            ->assertSet('replyingToId', $thread->getKey())
            ->set('replyMessage', 'Balasan baru dari composer chat.')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSet('replyMessage', '')
            ->assertSet('replyingToId', null);

        $this->assertDatabaseHas('comments', [
            'parent_id' => $thread->id,
            'user_id' => $parent->id,
            'comment' => 'Balasan baru dari composer chat.',
        ]);

        $this->get(ActivityCommentResource::getUrl())
            ->assertOk()
            ->assertSee('activity-conversation', false)
            ->assertSee($teacherUser->name)
            ->assertSee($student->name)
            ->assertSee('Balasan baru dari composer chat.')
            ->assertDontSee('wire:click="selectReply(', false);

        $this->get(ActivityCommentResource::getUrl('view', ['record' => $thread]))
            ->assertOk()
            ->assertSee('Pesan awal dari guru.')
            ->assertSee('Balasan orang tua dalam utas yang sama.')
            ->assertSee('Balasan baru dari composer chat.')
            ->assertSee('discussion-chat', false)
            ->assertSee('discussion-message--incoming', false)
            ->assertSee('discussion-message--outgoing', false)
            ->assertSee('discussion-composer', false)
            ->assertSee('wire:click="selectReply(', false)
            ->assertSee("mountAction('editMessage'", false);
    }

    public function test_teacher_inbox_separates_conversations_by_parent_name(): void
    {
        $teacherUser = User::role('guru')->firstOrFail();
        $firstStudent = Student::query()->with('parent.user')->firstOrFail();
        $secondParentUser = User::factory()->create([
            'name' => 'Nur Aisyah',
            'email' => 'nur.aisyah@sddarusalam.test',
            'status' => 'active',
        ]);
        $secondParentUser->assignRole('orang_tua');
        $secondParent = ParentProfile::create([
            'user_id' => $secondParentUser->id,
            'father_name' => 'Rahmat',
            'mother_name' => 'Nur Aisyah',
        ]);
        $secondStudent = Student::create([
            'class_id' => $firstStudent->class_id,
            'parent_id' => $secondParent->id,
            'nis' => 'SDD-CHAT-002',
            'name' => 'Nadia Rahma',
            'status' => 'active',
        ]);

        foreach ([$firstStudent, $secondStudent] as $index => $student) {
            $activity = SchoolActivity::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherUser->teacher->id,
                'activity_date' => today()->subDays($index),
                'attendance' => 'present',
            ]);
            ActivityComment::create([
                'activity_type' => SchoolActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $teacherUser->id,
                'comment' => "Pesan untuk keluarga {$student->name}.",
            ]);
        }

        $this->actingAs($teacherUser);
        $response = $this->get(ActivityCommentResource::getUrl())
            ->assertOk()
            ->assertSee($firstStudent->parent->user->name)
            ->assertSee($secondParentUser->name)
            ->assertSee($firstStudent->name)
            ->assertSee($secondStudent->name)
            ->assertDontSee('wire:click="selectReply(', false);

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($response->getContent(), 'class="activity-conversation"'),
        );
    }

    public function test_parent_schedule_response_is_upserted_per_agenda(): void
    {
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $schedule = Schedule::create([
            'class_id' => $student->class_id,
            'created_by' => User::role('guru')->firstOrFail()->id,
            'title' => 'Pertemuan Wali Murid',
            'activity_date' => today()->addWeek(),
        ]);

        $this->actingAs($parent);

        ScheduleResource::saveParentResponse($schedule, [
            'response' => 'attending',
            'note' => 'Akan hadir.',
        ]);
        ScheduleResource::saveParentResponse($schedule, [
            'response' => 'reschedule',
            'proposed_date' => today()->addDays(10)->toDateString(),
            'note' => 'Mohon jadwal pengganti.',
        ]);

        $this->assertDatabaseCount('schedule_responses', 1);
        $this->assertDatabaseHas('schedule_responses', [
            'schedule_id' => $schedule->id,
            'user_id' => $parent->id,
            'response' => 'reschedule',
            'note' => 'Mohon jadwal pengganti.',
        ]);

        $this->get(ScheduleResource::getUrl())
            ->assertOk()
            ->assertSee('Pertemuan Wali Murid')
            ->assertSee('Konfirmasi Saya')
            ->assertSee('Jadwalkan Ulang')
            ->assertSee('Konfirmasi');

        $this->get(ScheduleResource::getUrl('view', ['record' => $schedule]))
            ->assertOk()
            ->assertSee('Konfirmasi Kehadiran Saya')
            ->assertSee('Mohon jadwal pengganti.');
    }
}
