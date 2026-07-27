<?php

namespace Tests\Feature;

use App\Filament\Pages\DailyHomeActivities;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\ParentProfiles\Pages\EditParentProfile;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\ParentSubmissions\Pages\CreateParentSubmission;
use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Models\ActivityComment;
use App\Models\HomeActivity;
use App\Models\ParentProfile;
use App\Models\ParentSubmission;
use App\Models\Schedule;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_parent_navigation_opens_and_updates_only_their_profile(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $profile = $parentUser->parentProfile;

        $this->actingAs($parentUser);

        $this->assertTrue(ParentProfileResource::canView($profile));
        $this->assertTrue(ParentProfileResource::canEdit($profile));
        $this->assertFalse(ParentProfileResource::canCreate());
        $this->assertFalse(ParentProfileResource::canDelete($profile));
        $this->assertStringContainsString("/admin/parent-profiles/{$profile->id}", ParentProfileResource::getNavigationUrl());

        $this->get(ParentProfileResource::getNavigationUrl())
            ->assertOk()
            ->assertSee('family-mobile-nav', false)
            ->assertSee('Navigasi utama ponsel')
            ->assertSee('Profil Orang Tua')
            ->assertSee($parentUser->name)
            ->assertSee($profile->father_name);

        Livewire::test(EditParentProfile::class, ['record' => $profile->getRouteKey()])
            ->fillForm([
                'profile_avatar' => null,
                'profile_name' => 'Orang Tua Ahmad',
                'profile_email' => 'ortu-ahmad@sddarusalam.test',
                'profile_phone' => '081234567890',
                'father_name' => 'Bapak Ahmad',
                'mother_name' => 'Ibu Ahmad',
                'address' => 'Alamat keluarga Ahmad',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $parentUser->id,
            'name' => 'Orang Tua Ahmad',
            'email' => 'ortu-ahmad@sddarusalam.test',
            'phone' => '081234567890',
        ]);
        $this->assertDatabaseHas('parents', [
            'id' => $profile->id,
            'father_name' => 'Bapak Ahmad',
            'mother_name' => 'Ibu Ahmad',
            'phone' => '081234567890',
            'address' => 'Alamat keluarga Ahmad',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'orang-tua-lain@sddarusalam.test',
            'status' => 'active',
        ]);
        $otherUser->assignRole('orang_tua');
        $otherProfile = ParentProfile::create(['user_id' => $otherUser->id]);

        $this->assertFalse(ParentProfileResource::canView($otherProfile));
        $this->get(ParentProfileResource::getUrl('view', ['record' => $otherProfile]))
            ->assertNotFound();
    }

    public function test_parent_child_workspace_is_a_readonly_card_with_class_context(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $student = $parentUser->accessibleStudents()->firstOrFail();

        $unrelatedStudent = Student::create([
            'nis' => 'SDD-UNRELATED-001',
            'name' => 'Siswa Keluarga Lain',
            'status' => 'active',
        ]);

        $this->actingAs($parentUser);

        $this->assertFalse(StudentResource::canEdit($student));

        $this->get(StudentResource::getUrl())
            ->assertOk()
            ->assertSee('Anak Saya')
            ->assertSee('student-family-card', false)
            ->assertSee($student->name)
            ->assertSee($student->class->name)
            ->assertSee($student->class->teacher->user->name)
            ->assertSee('Lihat Profil Anak')
            ->assertDontSee($unrelatedStudent->name)
            ->assertDontSee(StudentResource::getUrl('edit', ['record' => $student]), false);

        $this->get(StudentResource::getUrl('view', ['record' => $student]))
            ->assertOk()
            ->assertSee('Profil Anak')
            ->assertSee('Wali Kelas')
            ->assertDontSee(StudentResource::getUrl('edit', ['record' => $student]), false);

        $this->get(StudentResource::getUrl('view', ['record' => $unrelatedStudent]))
            ->assertNotFound();
    }

    public function test_parent_can_only_complete_the_fixed_home_activity_checklist_for_their_child(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $student = $parentUser->accessibleStudents()->firstOrFail();
        $activity = HomeActivity::create([
            'student_id' => $student->id,
            'parent_id' => $student->parent_id,
            'activity_date' => today(),
            'activity_groups' => HomeActivity::defaultActivityGroupsForGrade($student->class->grade_level),
        ]);
        $this->actingAs($parentUser);

        $this->assertFalse(HomeActivityResource::canCreate());

        Livewire::test(DailyHomeActivities::class)
            ->set('selectedStudentId', $student->id)
            ->set('selectedDate', today()->toDateString())
            ->set('checks.subuh-prayer', true)
            ->set('note', 'Laporan otomatis dari hubungan keluarga.')
            ->call('saveChecklist')
            ->assertHasNoErrors();

        $activity->refresh();

        $this->assertSame($student->id, $activity->student_id);
        $this->assertSame($student->parent_id, $activity->parent_id);
        $this->assertTrue(collect($activity->activity_groups)
            ->flatMap(fn (array $group) => $group['items'])
            ->firstWhere('key', 'subuh-prayer')['checked']);
        $this->assertSame('Melaksanakan salat Subuh', $activity->activity_groups[0]['items'][0]['label']);
    }

    public function test_home_reports_follow_parent_relationship_changes(): void
    {
        $originalParent = User::role('orang_tua')->firstOrFail();
        $student = $originalParent->accessibleStudents()->firstOrFail();

        $newParentUser = User::factory()->create([
            'email' => 'orang-tua-pengganti@sddarusalam.test',
            'status' => 'active',
        ]);
        $newParentUser->assignRole('orang_tua');
        $newParent = ParentProfile::create(['user_id' => $newParentUser->id]);

        $activity = HomeActivity::create([
            'student_id' => $student->id,
            'parent_id' => $newParent->id,
            'activity_date' => today(),
        ]);

        $this->assertSame($student->parent_id, $activity->parent_id);

        $student->update(['parent_id' => $newParent->id]);
        $activity->refresh();

        $this->assertSame($newParent->id, $activity->parent_id);

        $this->actingAs($originalParent);
        $this->assertFalse(HomeActivityResource::canEdit($activity));

        $this->actingAs($newParentUser);
        $this->assertFalse(HomeActivityResource::canEdit($activity));
        $this->assertTrue(DailyHomeActivities::canAccess());
    }

    public function test_parent_selects_a_child_and_class_teacher_context_is_filled_automatically(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $firstStudent = $parentUser->accessibleStudents()->firstOrFail();
        $teacherUser = User::factory()->create([
            'name' => 'Guru Kelas Dua',
            'email' => 'guru-kelas-dua@sddarusalam.test',
            'status' => 'active',
        ]);
        $teacherUser->assignRole('guru');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '197001012000011020',
        ]);
        $secondClass = SchoolClass::create([
            'teacher_id' => $teacher->id,
            'name' => 'Kelas 2B',
            'grade_level' => 2,
            'capacity' => 30,
        ]);
        $secondStudent = Student::create([
            'class_id' => $secondClass->id,
            'parent_id' => $parentUser->parentProfile->id,
            'nis' => 'SDD-SIBLING-002',
            'name' => 'Aisyah Darusalam',
            'status' => 'active',
        ]);

        $this->actingAs($parentUser);
        $this->assertTrue(ParentSubmissionResource::canCreate());

        Livewire::test(CreateParentSubmission::class)
            ->assertSee($firstStudent->name)
            ->assertSee($secondStudent->name)
            ->fillForm([
                'student_id' => $secondStudent->id,
                'type' => 'permission',
                'start_date' => today()->toDateString(),
                'end_date' => today()->toDateString(),
                'description' => 'Mengikuti acara keluarga di luar kota.',
            ])
            ->assertSee($secondClass->name)
            ->assertSee($teacherUser->name)
            ->call('create')
            ->assertHasNoFormErrors();

        $submission = ParentSubmission::query()->latest('id')->firstOrFail();

        $this->assertSame($parentUser->parentProfile->id, $submission->parent_id);
        $this->assertSame($secondStudent->id, $submission->student_id);
        $this->assertSame('Izin - '.$secondStudent->name, $submission->title);
        $this->assertSame('pending', $submission->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacherUser->id,
            'title' => 'Laporan Orang Tua: '.$secondStudent->name,
            'is_read' => false,
        ]);
    }

    public function test_parent_actions_follow_report_comment_schedule_and_notification_ownership(): void
    {
        $parentUser = User::role('orang_tua')->firstOrFail();
        $teacherUser = User::role('guru')->firstOrFail();
        $student = $parentUser->accessibleStudents()->firstOrFail();
        $teacher = Teacher::where('user_id', $teacherUser->id)->firstOrFail();
        $profile = $parentUser->parentProfile;

        $schoolActivity = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);
        $homeActivity = HomeActivity::create([
            'student_id' => $student->id,
            'parent_id' => $profile->id,
            'activity_date' => today(),
            'study' => true,
        ]);
        $teacherComment = ActivityComment::create([
            'activity_type' => SchoolActivity::class,
            'activity_id' => $schoolActivity->id,
            'user_id' => $teacherUser->id,
            'comment' => 'Catatan dari guru.',
        ]);
        $parentComment = ActivityComment::create([
            'parent_id' => $teacherComment->id,
            'activity_type' => SchoolActivity::class,
            'activity_id' => $schoolActivity->id,
            'user_id' => $parentUser->id,
            'comment' => 'Tanggapan dari orang tua.',
        ]);
        $schedule = Schedule::create([
            'class_id' => $student->class_id,
            'created_by' => $teacherUser->id,
            'title' => 'Agenda Kelas Anak',
            'activity_date' => today(),
        ]);
        $notification = UserNotification::create([
            'user_id' => $parentUser->id,
            'created_by' => $teacherUser->id,
            'title' => 'Pesan untuk Orang Tua',
            'message' => 'Silakan periksa laporan anak.',
        ]);

        $this->actingAs($parentUser);

        $this->get(SchoolActivityResource::getUrl())
            ->assertOk()
            ->assertSee($student->name)
            ->assertDontSee(SchoolActivityResource::getUrl('edit', ['record' => $schoolActivity]), false);

        $this->get(SchoolActivityResource::getUrl('view', ['record' => $schoolActivity]))
            ->assertOk()
            ->assertSee('Ringkasan Aktivitas')
            ->assertDontSee(SchoolActivityResource::getUrl('edit', ['record' => $schoolActivity]), false);

        $this->get(HomeActivityResource::getUrl())
            ->assertOk()
            ->assertSee($student->name)
            ->assertDontSee(HomeActivityResource::getUrl('edit', ['record' => $homeActivity]), false);

        $this->get(ActivityCommentResource::getUrl())
            ->assertOk()
            ->assertSee($parentComment->comment)
            ->assertDontSee(ActivityCommentResource::getUrl('edit', ['record' => $teacherComment]), false)
            ->assertDontSee(ActivityCommentResource::getUrl('edit', ['record' => $parentComment]), false);

        $this->get(ActivityCommentResource::getUrl('view', ['record' => $teacherComment]))
            ->assertOk()
            ->assertSee($teacherComment->comment)
            ->assertSee($parentComment->comment)
            ->assertSee('discussion-topic', false)
            ->assertSee('Riwayat tanggapan');

        $this->get(ScheduleResource::getUrl())
            ->assertOk()
            ->assertSee($schedule->title)
            ->assertDontSee(ScheduleResource::getUrl('edit', ['record' => $schedule]), false);

        $this->get(UserNotificationResource::getUrl())
            ->assertOk()
            ->assertSee($notification->title)
            ->assertDontSee(UserNotificationResource::getUrl('edit', ['record' => $notification]), false);

        $this->get(SchoolActivityResource::getUrl('edit', ['record' => $schoolActivity]))->assertForbidden();
        $this->get(HomeActivityResource::getUrl('edit', ['record' => $homeActivity]))->assertForbidden();
        $this->get(DailyHomeActivities::getUrl())->assertOk();
        $this->get(ActivityCommentResource::getUrl('edit', ['record' => $teacherComment]))->assertForbidden();
        $this->get(ActivityCommentResource::getUrl('edit', ['record' => $parentComment]))->assertOk();
        $this->get(ScheduleResource::getUrl('edit', ['record' => $schedule]))->assertForbidden();
        $this->get(UserNotificationResource::getUrl('edit', ['record' => $notification]))->assertForbidden();
    }
}
