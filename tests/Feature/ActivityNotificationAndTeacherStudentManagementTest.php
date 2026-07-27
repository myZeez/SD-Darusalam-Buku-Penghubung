<?php

namespace Tests\Feature;

use App\Filament\Forms\CompressedImageUpload;
use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentsImport;
use App\Models\ActivityComment;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityNotificationAndTeacherStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_activity_comments_notify_the_other_party_with_a_link_to_the_thread(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $parent = User::role('orang_tua')->firstOrFail();
        $student = $parent->accessibleStudents()->firstOrFail();
        $activity = SchoolActivity::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->teacher->id,
            'activity_date' => today(),
            'attendance' => 'present',
        ]);

        $thread = ActivityComment::create([
            'activity_type' => SchoolActivity::class,
            'activity_id' => $activity->id,
            'user_id' => $teacher->id,
            'comment' => 'Bagaimana perkembangan kegiatan belajar di rumah?',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $parent->id,
            'created_by' => $teacher->id,
            'title' => 'Tanggapan Baru dari '.$teacher->name,
            'action_url' => ActivityCommentResource::getUrl('view', ['record' => $thread]),
        ]);

        $this->actingAs($parent);
        $reply = ActivityCommentResource::replyTo($thread, 'Anak sudah belajar dan beristirahat cukup.');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'created_by' => $parent->id,
            'title' => 'Tanggapan Baru dari '.$parent->name,
            'action_url' => ActivityCommentResource::getUrl('view', ['record' => $thread]),
        ]);
        $this->assertSame($thread->id, $reply->parent_id);
        $this->assertSame(2, UserNotification::query()->whereIn('user_id', [$teacher->id, $parent->id])->count());

        $this->get(SchoolActivityResource::getUrl('view', ['record' => $activity]))
            ->assertOk()
            ->assertSee('Tanggapi');
    }

    public function test_image_uploads_are_resized_before_uploading(): void
    {
        $upload = CompressedImageUpload::make('photo', 'Foto Aktivitas', 'school-activities');

        $this->assertSame('1600', $upload->getAutomaticallyResizeImagesWidth());
        $this->assertSame('1600', $upload->getAutomaticallyResizeImagesHeight());
        $this->assertSame('contain', $upload->getAutomaticallyResizeImagesMode());
        $this->assertSame(5120, $upload->getMaxSize());
    }

    public function test_teacher_can_manage_and_import_students_only_for_their_own_class(): void
    {
        $teacher = User::role('guru')->firstOrFail();
        $ownClass = $teacher->managedClasses()->firstOrFail();
        $ownStudent = $teacher->managedStudents()->firstOrFail();
        $otherClass = $this->createOtherClass();
        $otherStudent = Student::create([
            'class_id' => $otherClass->id,
            'nis' => 'SDD-MUTASI-LUAR-001',
            'name' => 'Siswa Kelas Lain',
            'status' => 'active',
        ]);

        $this->actingAs($teacher);

        $this->assertTrue(StudentResource::shouldRegisterNavigation());
        $this->assertTrue(StudentResource::canCreate());
        $this->assertTrue(StudentResource::canEdit($ownStudent));
        $this->assertFalse(StudentResource::canEdit($otherStudent));
        $this->assertFalse(StudentResource::canDelete($ownStudent));
        $this->assertTrue(StudentResource::canManageClass($ownClass->id));
        $this->assertFalse(StudentResource::canManageClass($otherClass->id));

        $this->get(StudentResource::getUrl())
            ->assertOk()
            ->assertSee($ownStudent->name)
            ->assertDontSee($otherStudent->name);

        $this->get(StudentResource::getUrl('create', ['class_id' => $ownClass->id]))
            ->assertOk()
            ->assertSee($ownClass->name)
            ->assertDontSee($otherClass->name);

        $import = new StudentsImport($teacher);
        $allowedStudent = $import->model([
            'class_id' => $ownClass->id,
            'nis' => 'SDD-IMPORT-GURU-001',
            'name' => 'Siswa Impor Guru',
        ]);
        $this->assertSame($ownClass->id, $allowedStudent->class_id);

        try {
            $import->model([
                'class_id' => $otherClass->id,
                'nis' => 'SDD-IMPORT-TERLARANG-001',
                'name' => 'Siswa Impor Terlarang',
            ]);
            $this->fail('Guru tidak boleh mengimpor siswa ke kelas lain.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
    }

    private function createOtherClass(): SchoolClass
    {
        $teacherUser = User::factory()->create([
            'email' => 'guru-mutasi-lain@sddarusalam.test',
            'status' => 'active',
        ]);
        $teacherUser->assignRole('guru');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '197001012000011008',
        ]);

        return SchoolClass::create([
            'teacher_id' => $teacher->id,
            'name' => 'Kelas Mutasi 2A',
            'grade_level' => 2,
            'capacity' => 30,
        ]);
    }
}
