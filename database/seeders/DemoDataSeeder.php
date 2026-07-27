<?php

namespace Database\Seeders;

use App\Models\ActivityComment;
use App\Models\AttendanceRecord;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularEnrollment;
use App\Models\ExtracurricularScore;
use App\Models\ExtracurricularSession;
use App\Models\HomeActivity;
use App\Models\ParentProfile;
use App\Models\ParentSubmission;
use App\Models\PasswordResetRequest;
use App\Models\Schedule;
use App\Models\ScheduleResponse;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\SchoolActivityTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    private const STUDENT_COUNT = 50;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        DB::transaction(function (): void {
            $coreUsers = $this->seedCoreUsers();
            $settings = $this->seedSchoolSettings();
            $teachers = $this->seedTeachers();
            $classes = $this->seedClasses($teachers);
            $families = $this->seedFamiliesAndStudents($classes);
            $demoStartDate = CarbonImmutable::now($settings->timezone)->startOfMonth();

            $schoolDays = $this->recentSchoolDays(10, $settings->timezone);
            $attendancePlan = $this->buildAttendancePlan($families, $schoolDays);
            $submissions = $this->seedParentSubmissions(
                $families,
                $attendancePlan,
                $coreUsers['admin'],
                $demoStartDate,
            );

            $this->seedAttendance(
                $families,
                $schoolDays,
                $attendancePlan,
                $submissions,
                $coreUsers['gate'],
                $coreUsers['admin'],
            );
            $this->seedDailyActivities($families, $schoolDays, $attendancePlan);
            $this->seedDiscussions($families, $schoolDays->last());

            $schedules = $this->seedSchedules(
                $classes,
                $coreUsers['admin'],
                $demoStartDate,
            );
            $this->seedScheduleResponses($schedules, $families);
            $this->seedNotifications($families, $teachers, $coreUsers['admin']);
            $this->seedPasswordResetRequests($families);
            $this->seedExtracurriculars(
                $families,
                $teachers,
                $demoStartDate,
            );
        });

        $this->printSummary();
    }

    /** @return array{admin: User, gate: User} */
    private function seedCoreUsers(): array
    {
        $adminRole = Role::findByName('admin');
        $gateRole = Role::findByName('loket');

        $admin = $this->upsertUser(
            'admin@sddarusalam.test',
            'Administrator Sekolah',
            '080000000001',
            'Administrator Sekolah',
            $adminRole,
        );

        $this->upsertUser(
            'kepala.sekolah@sddarusalam.test',
            'Drs. H. Abdullah Karim',
            '080000000005',
            'Kepala Sekolah',
            $adminRole,
        );

        $gate = $this->upsertUser(
            'loket@sddarusalam.test',
            'Petugas Loket',
            '080000000006',
            'Petugas Loket',
            $gateRole,
        );

        return compact('admin', 'gate');
    }

    private function seedSchoolSettings(): SchoolSetting
    {
        $settings = SchoolSetting::current();
        $settings->update([
            'school_name' => 'SD Islam Darusalam',
            'npsn' => '69999999',
            'principal_name' => 'Drs. H. Abdullah Karim',
            'address' => 'Jl. Pendidikan No. 10, Kota Bandung, Jawa Barat',
            'phone' => '022-7000000',
            'email' => 'sekolah@sddarusalam.test',
            'school_start_time' => '07:00:00',
            'late_tolerance_minutes' => 10,
            'school_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'Asia/Jakarta',
        ]);

        return $settings;
    }

    /** @return Collection<int, Teacher> */
    private function seedTeachers(): Collection
    {
        $teacherRole = Role::findByName('guru');
        $definitions = [
            ['guru@sddarusalam.test', 'Budi Aulyansyah, S.T.', '197901012005011001', 'male', '081210000001'],
            ['guru2@sddarusalam.test', 'Siti Rahmawati, S.Pd.', '198202142006022002', 'female', '081210000002'],
            ['guru3@sddarusalam.test', 'Ahmad Fauzan, S.Pd.I.', '198503192008011003', 'male', '081210000003'],
            ['guru4@sddarusalam.test', 'Dewi Lestari, S.Pd.', '198708252010022004', 'female', '081210000004'],
            ['guru5@sddarusalam.test', 'Rizky Pratama, S.Or.', '199001102012011005', 'male', '081210000005'],
            ['guru6@sddarusalam.test', 'Aulia Khasanah, S.Pd.', '199203122014012006', 'female', '081210000006'],
            ['guru7@sddarusalam.test', 'Lina Marlina, S.Pd.', '199408212016022007', 'female', '081210000007'],
            ['guru8@sddarusalam.test', 'Yusuf Maulana, S.Pd.', '199105172013011008', 'male', '081210000008'],
            ['guru9@sddarusalam.test', 'Maya Safitri, S.Pd.', '199310082015022009', 'female', '081210000009'],
            ['guru10@sddarusalam.test', 'Hasan Basri, S.Pd.I.', '199002282012011010', 'male', '081210000010'],
        ];

        return collect($definitions)->map(function (array $definition) use ($teacherRole): Teacher {
            [$email, $name, $nip, $gender, $phone] = $definition;
            $user = $this->upsertUser($email, $name, $phone, 'Guru', $teacherRole);

            return Teacher::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $nip,
                    'gender' => $gender,
                    'address' => 'Kompleks Guru SD Islam Darusalam, Kota Bandung',
                ],
            );
        })->values();
    }

    /**
     * @param  Collection<int, Teacher>  $teachers
     * @return Collection<int, SchoolClass>
     */
    private function seedClasses(Collection $teachers): Collection
    {
        $definitions = [
            ['Kelas 1A', 1, 'Ruang 1A', 0],
            ['Kelas 1B', 1, 'Ruang 1B', 1],
            ['Kelas 2A', 2, 'Ruang 2A', 3],
            ['Kelas 2B', 2, 'Ruang 2B', 4],
            ['Kelas 3A', 3, 'Ruang 3A', 5],
        ];

        return collect($definitions)->map(function (array $definition) use ($teachers): SchoolClass {
            [$name, $grade, $room, $teacherIndex] = $definition;

            return SchoolClass::query()->updateOrCreate(
                [
                    'name' => $name,
                ],
                [
                    'teacher_id' => $teachers[$teacherIndex]->id,
                    'grade_level' => $grade,
                    'capacity' => 30,
                    'room' => $room,
                    'description' => "Rombongan belajar {$name}.",
                ],
            );
        })->values();
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @return Collection<int, array{index: int, student: Student, student_user: User, parent: ParentProfile, parent_user: User, class: SchoolClass}>
     */
    private function seedFamiliesAndStudents(Collection $classes): Collection
    {
        $parentRole = Role::findByName('orang_tua');
        $studentRole = Role::findByName('siswa');
        $firstNames = [
            ['Ahmad', 'male'],
            ['Aisyah', 'female'],
            ['Muhammad', 'male'],
            ['Zahra', 'female'],
            ['Fajar', 'male'],
            ['Nabila', 'female'],
            ['Rizky', 'male'],
            ['Alya', 'female'],
            ['Dimas', 'male'],
            ['Salsabila', 'female'],
        ];
        $lastNames = ['Darusalam', 'Ramadhan', 'Pratama', 'Lestari', 'Hidayat'];
        $families = collect();

        for ($index = 0; $index < self::STUDENT_COUNT; $index++) {
            [$firstName, $gender] = $firstNames[$index % count($firstNames)];
            $lastName = $lastNames[intdiv($index, count($firstNames))];
            $studentName = "{$firstName} {$lastName}";
            $number = $index + 1;
            $suffix = sprintf('%02d', $number);
            $class = $classes[$index % $classes->count()];
            $studentEmail = $number === 1 ? 'siswa@sddarusalam.test' : "siswa{$suffix}@sddarusalam.test";
            $parentEmail = $number === 1 ? 'ortu@sddarusalam.test' : "ortu{$suffix}@sddarusalam.test";

            $parentUser = $this->upsertUser(
                $parentEmail,
                "Orang Tua {$studentName}",
                sprintf('08122000%04d', $number),
                'Orang Tua / Wali Murid',
                $parentRole,
            );
            $parent = ParentProfile::query()->updateOrCreate(
                ['user_id' => $parentUser->id],
                [
                    'father_name' => "Bapak {$lastName}",
                    'mother_name' => "Ibu {$lastName}",
                    'phone' => $parentUser->phone,
                    'address' => 'Jl. Keluarga Pelajar No. '.$number.', Kota Bandung',
                ],
            );

            $studentUser = $this->upsertUser(
                $studentEmail,
                $studentName,
                sprintf('08123000%04d', $number),
                "Siswa {$class->name}",
                $studentRole,
            );
            $birthYear = 2020 - (int) $class->grade_level;
            $student = Student::query()->updateOrCreate(
                ['nis' => sprintf('SDD-%03d', $number)],
                [
                    'user_id' => $studentUser->id,
                    'class_id' => $class->id,
                    'parent_id' => $parent->id,
                    'name' => $studentName,
                    'gender' => $gender,
                    'birth_date' => sprintf('%d-%02d-%02d', $birthYear, ($index % 12) + 1, ($index % 27) + 1),
                    'status' => 'active',
                ],
            );

            $families->push([
                'index' => $index,
                'student' => $student,
                'student_user' => $studentUser,
                'parent' => $parent,
                'parent_user' => $parentUser,
                'class' => $class,
            ]);
        }

        return $families;
    }

    /** @return Collection<int, CarbonImmutable> */
    private function recentSchoolDays(int $count, string $timezone): Collection
    {
        $days = collect();
        $date = CarbonImmutable::now($timezone)->startOfDay();

        while ($days->count() < $count) {
            if ($date->isWeekday()) {
                $days->push($date);
            }

            $date = $date->subDay();
        }

        return $days->reverse()->values();
    }

    /**
     * @param  Collection<int, array{index: int, student: Student}>  $families
     * @param  Collection<int, CarbonImmutable>  $schoolDays
     * @return array<string, string>
     */
    private function buildAttendancePlan(Collection $families, Collection $schoolDays): array
    {
        $plan = [];

        foreach ($families as $family) {
            foreach ($schoolDays as $dayIndex => $date) {
                $seed = ($family['index'] + 1) + (($dayIndex + 1) * 3);
                $status = $seed % 31 === 0
                    ? 'sick'
                    : ($seed % 37 === 0 ? 'permission' : 'present');
                $plan[$this->attendanceKey($family['student']->id, $date)] = $status;
            }
        }

        return $plan;
    }

    /**
     * @param  Collection<int, array{student: Student, parent: ParentProfile}>  $families
     * @param  array<string, string>  $attendancePlan
     * @return array<string, ParentSubmission>
     */
    private function seedParentSubmissions(
        Collection $families,
        array $attendancePlan,
        User $reviewer,
        CarbonImmutable $periodStart,
    ): array {
        $submissions = [];

        foreach ($families as $family) {
            foreach ($attendancePlan as $key => $status) {
                if (! str_starts_with($key, $family['student']->id.'|') || $status === 'present') {
                    continue;
                }

                $date = CarbonImmutable::parse(explode('|', $key, 2)[1]);
                $typeLabel = $status === 'sick' ? 'Sakit' : 'Izin';
                $submission = $this->upsertParentSubmission(
                    [
                        'student_id' => $family['student']->id,
                        'type' => $status,
                        'start_date' => $date,
                        'title' => "{$typeLabel} - ".$date->format('d-m-Y'),
                    ],
                    [
                        'parent_id' => $family['parent']->id,
                        'reviewed_by' => $reviewer->id,
                        'description' => $status === 'sick'
                            ? 'Siswa beristirahat di rumah dan telah mendapatkan penanganan dari keluarga.'
                            : 'Siswa mendapat izin keluarga dan tidak dapat mengikuti pembelajaran pada hari tersebut.',
                        'end_date' => $date,
                        'status' => 'approved',
                        'review_note' => 'Disetujui untuk data demonstrasi. Orang tua diminta memantau materi yang tertinggal.',
                        'reviewed_at' => $date->setTime(6, 30),
                    ],
                );

                $submissions[$key] = $submission;
            }
        }

        foreach ($families->take(3) as $offset => $family) {
            $date = $periodStart->addDays(35 + $offset);
            $this->upsertParentSubmission(
                [
                    'student_id' => $family['student']->id,
                    'type' => 'family',
                    'start_date' => $date,
                    'title' => 'Keperluan Keluarga',
                ],
                [
                    'parent_id' => $family['parent']->id,
                    'description' => 'Menghadiri acara keluarga di luar kota selama satu hari.',
                    'end_date' => $date,
                    'status' => $offset === 2 ? 'rejected' : 'pending',
                    'reviewed_by' => $offset === 2 ? $reviewer->id : null,
                    'review_note' => $offset === 2 ? 'Tanggal bertepatan dengan asesmen kelas.' : null,
                    'reviewed_at' => $offset === 2 ? $date->subDay() : null,
                ],
            );
        }

        return $submissions;
    }

    /**
     * @param  Collection<int, array{index: int, student: Student, class: SchoolClass}>  $families
     * @param  Collection<int, CarbonImmutable>  $schoolDays
     * @param  array<string, string>  $attendancePlan
     * @param  array<string, ParentSubmission>  $submissions
     */
    private function seedAttendance(
        Collection $families,
        Collection $schoolDays,
        array $attendancePlan,
        array $submissions,
        User $gateUser,
        User $admin,
    ): void {
        $now = now();
        $arrivalRows = [];

        foreach ($families as $family) {
            foreach ($schoolDays as $dayIndex => $date) {
                $key = $this->attendanceKey($family['student']->id, $date);

                if ($attendancePlan[$key] !== 'present') {
                    continue;
                }

                $isLate = (($family['index'] + $dayIndex) % 11) === 0;
                $arrivalRows[] = [
                    'student_id' => $family['student']->id,
                    'class_id' => $family['class']->id,
                    'recorded_by' => $gateUser->id,
                    'arrival_date' => $date,
                    'arrival_time' => $isLate ? '07:16:00' : sprintf('06:%02d:00', 35 + (($family['index'] + $dayIndex) % 20)),
                    'status' => $isLate ? 'late' : 'on_time',
                    'notes' => $isLate ? 'Terlambat karena kondisi lalu lintas.' : 'Check-in melalui loket sekolah.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('student_arrivals')->upsert(
            $arrivalRows,
            ['student_id', 'arrival_date'],
            ['class_id', 'recorded_by', 'arrival_time', 'status', 'notes', 'updated_at'],
        );

        $arrivalMap = StudentArrival::query()
            ->whereIn('student_id', $families->pluck('student.id'))
            ->whereIn('arrival_date', $schoolDays)
            ->get()
            ->keyBy(fn (StudentArrival $arrival): string => $this->attendanceKey(
                $arrival->student_id,
                CarbonImmutable::parse($arrival->arrival_date),
            ));
        $attendanceRows = [];

        foreach ($families as $family) {
            foreach ($schoolDays as $date) {
                $key = $this->attendanceKey($family['student']->id, $date);
                $status = $attendancePlan[$key];
                $arrival = $arrivalMap->get($key);
                $submission = $submissions[$key] ?? null;

                $attendanceRows[] = [
                    'student_id' => $family['student']->id,
                    'class_id' => $family['class']->id,
                    'arrival_id' => $arrival?->id,
                    'parent_submission_id' => $submission?->id,
                    'recorded_by' => $status === 'present' ? $gateUser->id : $admin->id,
                    'attendance_date' => $date,
                    'status' => $status,
                    'is_late' => $arrival?->status === 'late',
                    'source' => $status === 'present' ? 'arrival' : 'parent_submission',
                    'notes' => $status === 'present'
                        ? ($arrival?->status === 'late' ? 'Hadir dan tercatat terlambat.' : 'Hadir tepat waktu.')
                        : $submission?->description,
                    'verified_at' => $date->setTime(8, 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('attendance_records')->upsert(
            $attendanceRows,
            ['student_id', 'attendance_date'],
            [
                'class_id',
                'arrival_id',
                'parent_submission_id',
                'recorded_by',
                'status',
                'is_late',
                'source',
                'notes',
                'verified_at',
                'updated_at',
            ],
        );
    }

    /**
     * @param  Collection<int, array{index: int, student: Student, parent: ParentProfile, class: SchoolClass}>  $families
     * @param  Collection<int, CarbonImmutable>  $schoolDays
     * @param  array<string, string>  $attendancePlan
     */
    private function seedDailyActivities(Collection $families, Collection $schoolDays, array $attendancePlan): void
    {
        $now = now();
        $schoolRows = [];
        $homeRows = [];

        foreach ($families as $family) {
            foreach ($schoolDays->take(-5) as $dayIndex => $date) {
                $attendance = $attendancePlan[$this->attendanceKey($family['student']->id, $date)];
                $schoolRows[] = [
                    'student_id' => $family['student']->id,
                    'teacher_id' => $family['class']->teacher_id,
                    'activity_date' => $date,
                    'attendance' => $attendance,
                    'activity_groups' => json_encode($this->schoolActivityGroups($family['class']->grade_level, $family['index'], $attendance), JSON_UNESCAPED_UNICODE),
                    'morning_activity' => 'Literasi pagi dan doa bersama sebelum pembelajaran.',
                    'learning_activity' => 'Mengikuti pembelajaran tematik, diskusi kelompok, dan latihan mandiri.',
                    'religious_activity' => 'Membaca doa harian dan murajaah surat pendek.',
                    'character_building' => 'Berlatih disiplin, kerja sama, dan tanggung jawab.',
                    'motoric_activity' => 'Senam ringan dan permainan koordinasi gerak.',
                    'break_activity' => 'Makan bekal dan bermain bersama teman dengan tertib.',
                    'cleanliness' => 'Merapikan meja belajar dan membuang sampah pada tempatnya.',
                    'independence' => 'Menyiapkan serta menyimpan perlengkapan belajar secara mandiri.',
                    'note' => $attendance === 'present'
                        ? 'Perkembangan siswa baik dan aktif mengikuti kegiatan kelas.'
                        : 'Siswa tidak mengikuti kegiatan kelas karena '.($attendance === 'sick' ? 'sakit.' : 'izin.'),
                    'photo' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ($schoolDays->take(-4) as $dayIndex => $date) {
                $homeRows[] = [
                    'student_id' => $family['student']->id,
                    'parent_id' => $family['parent']->id,
                    'activity_date' => $date,
                    'activity_groups' => json_encode($this->homeActivityGroups($family['class']->grade_level, $family['index'], $dayIndex), JSON_UNESCAPED_UNICODE),
                    'worship' => true,
                    'study' => true,
                    'homework' => (($family['index'] + $dayIndex) % 5) !== 0,
                    'sleep' => (($family['index'] + $dayIndex) % 7) !== 0,
                    'meal' => true,
                    'note' => 'Belajar bersama orang tua selama 30 menit dan menyiapkan perlengkapan untuk esok hari.',
                    'photo' => null,
                    'submitted_at' => $date->setTime(20, 0),
                    'submitted_by' => $family['parent']->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('school_activities')->upsert(
            $schoolRows,
            ['student_id', 'activity_date'],
            [
                'teacher_id',
                'attendance',
                'activity_groups',
                'morning_activity',
                'learning_activity',
                'religious_activity',
                'character_building',
                'motoric_activity',
                'break_activity',
                'cleanliness',
                'independence',
                'note',
                'photo',
                'updated_at',
            ],
        );
        DB::table('home_activities')->upsert(
            $homeRows,
            ['student_id', 'activity_date'],
            ['parent_id', 'activity_groups', 'worship', 'study', 'homework', 'sleep', 'meal', 'note', 'photo', 'submitted_at', 'submitted_by', 'updated_at'],
        );
    }

    /** @return array<int, array{category: string, items: array<int, array<string, mixed>>}> */
    private function schoolActivityGroups(?int $gradeLevel, int $index, string $attendance): array
    {
        $present = $attendance === 'present';
        $groups = SchoolActivityTemplate::forGrade($gradeLevel);

        foreach ($groups as $groupIndex => &$group) {
            foreach ($group['items'] as $itemIndex => &$item) {
                $item['checked'] = $present && ($index + $groupIndex + $itemIndex) % 8 !== 0;
            }
            unset($item);
        }
        unset($group);

        return $groups;
    }

    /** @return array<int, array{category: string, items: array<int, array<string, mixed>>}> */
    private function homeActivityGroups(?int $gradeLevel, int $index, int $dayIndex): array
    {
        $groups = HomeActivity::defaultActivityGroupsForGrade($gradeLevel);

        foreach ($groups as $groupIndex => &$group) {
            foreach ($group['items'] as $itemIndex => &$item) {
                $item['checked'] = ($index + $dayIndex + $groupIndex + $itemIndex) % 6 !== 0;
            }
            unset($item);
        }
        unset($group);

        return $groups;
    }

    /**
     * @param  Collection<int, array{student: Student, parent_user: User, class: SchoolClass}>  $families
     */
    private function seedDiscussions(Collection $families, CarbonImmutable $latestDate): void
    {
        $schoolActivities = SchoolActivity::query()
            ->whereIn('student_id', $families->pluck('student.id'))
            ->whereDate('activity_date', $latestDate)
            ->get()
            ->keyBy('student_id');
        $homeActivities = HomeActivity::query()
            ->whereIn('student_id', $families->pluck('student.id'))
            ->whereDate('activity_date', $latestDate)
            ->get()
            ->keyBy('student_id');

        foreach ($families->take(15) as $family) {
            $activity = $schoolActivities->get($family['student']->id);

            if (! $activity) {
                continue;
            }

            $teacherUserId = $family['class']->teacher?->user_id;
            $root = ActivityComment::query()->firstOrCreate([
                'parent_id' => null,
                'activity_type' => SchoolActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $teacherUserId,
                'comment' => 'Ananda menunjukkan perkembangan yang baik. Mohon rutinitas membaca dilanjutkan di rumah.',
            ]);
            ActivityComment::query()->firstOrCreate([
                'parent_id' => $root->id,
                'activity_type' => SchoolActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $family['parent_user']->id,
                'comment' => 'Baik, Bu/Pak Guru. Kami akan mendampingi kegiatan membaca selama 20 menit malam ini.',
            ]);
            ActivityComment::query()->firstOrCreate([
                'parent_id' => $root->id,
                'activity_type' => SchoolActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $teacherUserId,
                'comment' => 'Terima kasih atas kerja samanya. Silakan kabari jika ada materi yang perlu dibantu.',
            ]);
        }

        foreach ($families->take(10) as $family) {
            $activity = $homeActivities->get($family['student']->id);

            if (! $activity) {
                continue;
            }

            $teacherUserId = $family['class']->teacher?->user_id;
            $root = ActivityComment::query()->firstOrCreate([
                'parent_id' => null,
                'activity_type' => HomeActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $family['parent_user']->id,
                'comment' => 'Anak sudah belajar dan mengerjakan tugas, tetapi masih perlu bantuan pada latihan berhitung.',
            ]);
            ActivityComment::query()->firstOrCreate([
                'parent_id' => $root->id,
                'activity_type' => HomeActivity::class,
                'activity_id' => $activity->id,
                'user_id' => $teacherUserId,
                'comment' => 'Terima kasih informasinya. Besok latihan berhitung akan kami ulangi secara bertahap di kelas.',
            ]);
        }
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @return Collection<int, Schedule>
     */
    private function seedSchedules(Collection $classes, User $admin, CarbonImmutable $periodStart): Collection
    {
        $definitions = [
            [null, $admin->id, 'Pertemuan Orang Tua Semester Ganjil', 24, 'Aula SD Islam Darusalam', '08:00:00', '10:00:00', 'Sosialisasi program sekolah, kurikulum, dan buku penghubung digital.'],
            [null, $admin->id, 'Pekan Olahraga dan Kesehatan', 31, 'Lapangan Sekolah', '07:30:00', '11:00:00', 'Kegiatan olahraga bersama, pemeriksaan kesehatan, dan edukasi pola hidup sehat.'],
            [null, $admin->id, 'Pentas Seni Siswa', 45, 'Aula SD Islam Darusalam', '09:00:00', '12:00:00', 'Penampilan kreativitas siswa dari setiap kelas dan ekstrakurikuler.'],
        ];

        foreach ($classes as $index => $class) {
            $definitions[] = [
                $class->id,
                $class->teacher?->user_id,
                'Pertemuan Wali Kelas '.$class->name,
                27 + ($index * 2),
                $class->room,
                '10:30:00',
                '11:30:00',
                'Evaluasi perkembangan awal semester dan koordinasi kegiatan kelas.',
            ];
        }

        return collect($definitions)->map(function (array $definition) use ($periodStart): Schedule {
            [$classId, $creatorId, $title, $dayOffset, $location, $startTime, $endTime, $description] = $definition;
            $activityDate = $periodStart->addDays($dayOffset);

            return Schedule::query()->updateOrCreate(
                [
                    'title' => $title,
                    'activity_date' => $activityDate,
                ],
                [
                    'class_id' => $classId,
                    'created_by' => $creatorId,
                    'description' => $description,
                    'location' => $location,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'equipment' => 'Buku catatan, alat tulis, dan botol minum pribadi.',
                ],
            );
        })->values();
    }

    /**
     * @param  Collection<int, Schedule>  $schedules
     * @param  Collection<int, array{index: int, parent_user: User}>  $families
     */
    private function seedScheduleResponses(Collection $schedules, Collection $families): void
    {
        foreach ($schedules as $scheduleIndex => $schedule) {
            foreach ($families->take(25) as $family) {
                $seed = $family['index'] + $scheduleIndex;
                $response = $seed % 13 === 0
                    ? 'reschedule'
                    : ($seed % 9 === 0 ? 'unavailable' : 'attending');

                ScheduleResponse::query()->updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'user_id' => $family['parent_user']->id,
                    ],
                    [
                        'response' => $response,
                        'proposed_date' => $response === 'reschedule' ? $schedule->activity_date->addWeek() : null,
                        'note' => match ($response) {
                            'attending' => 'Insyaallah hadir sesuai jadwal.',
                            'unavailable' => 'Belum dapat hadir karena ada keperluan keluarga.',
                            default => 'Mohon dipertimbangkan jadwal pada pekan berikutnya.',
                        },
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<int, array{student: Student, student_user: User, parent_user: User}>  $families
     * @param  Collection<int, Teacher>  $teachers
     */
    private function seedNotifications(Collection $families, Collection $teachers, User $admin): void
    {
        foreach ($families as $family) {
            UserNotification::query()->updateOrCreate(
                [
                    'user_id' => $family['parent_user']->id,
                    'title' => 'Ringkasan Mingguan '.$family['student']->nis,
                ],
                [
                    'created_by' => $admin->id,
                    'message' => 'Ringkasan presensi, laporan sekolah, kegiatan rumah, dan agenda terbaru sudah tersedia.',
                    'is_read' => $family['student']->id % 3 === 0,
                ],
            );
            UserNotification::query()->updateOrCreate(
                [
                    'user_id' => $family['student_user']->id,
                    'title' => 'Pengingat Kegiatan Belajar',
                ],
                [
                    'created_by' => $admin->id,
                    'message' => 'Siapkan buku pelajaran dan perlengkapan sesuai jadwal kelas besok.',
                    'is_read' => $family['student']->id % 4 === 0,
                ],
            );
        }

        foreach ($teachers as $teacher) {
            UserNotification::query()->updateOrCreate(
                [
                    'user_id' => $teacher->user_id,
                    'title' => 'Data Kelas Siap Ditinjau',
                ],
                [
                    'created_by' => $admin->id,
                    'message' => 'Data siswa, presensi, aktivitas sekolah, aktivitas rumah, dan agenda telah diperbarui.',
                    'is_read' => false,
                ],
            );
        }
    }

    /** @param Collection<int, array{index: int, student: Student, student_user: User, parent_user: User, class: SchoolClass}> $families */
    private function seedPasswordResetRequests(Collection $families): void
    {
        foreach ($families->take(4) as $family) {
            $isStudentAccount = $family['index'] % 2 === 0;
            $account = $isStudentAccount ? $family['student_user'] : $family['parent_user'];

            PasswordResetRequest::query()->updateOrCreate(
                [
                    'user_id' => $account->id,
                    'status' => 'pending',
                ],
                [
                    'student_id' => $family['student']->id,
                    'teacher_id' => $family['class']->teacher?->user_id,
                    'request_note' => $isStudentAccount
                        ? 'Siswa memerlukan bantuan masuk untuk melanjutkan pembelajaran.'
                        : 'Orang tua memerlukan bantuan masuk untuk memantau perkembangan siswa.',
                    'processed_by' => null,
                    'admin_note' => null,
                    'processed_at' => null,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, array{index: int, student: Student}>  $families
     * @param  Collection<int, Teacher>  $teachers
     */
    private function seedExtracurriculars(
        Collection $families,
        Collection $teachers,
        CarbonImmutable $periodStart,
    ): void {
        $definitions = [
            ['Pramuka', 0, 'friday', '14:00:00', '15:30:00', 'Lapangan Sekolah', 'Kegiatan kepanduan, kemandirian, dan kerja sama.'],
            ['Futsal', 4, 'saturday', '08:00:00', '09:30:00', 'Lapangan Futsal', 'Latihan teknik dasar, kebugaran, dan sportivitas.'],
            ['Tahfiz', 2, 'wednesday', '13:30:00', '14:30:00', 'Masjid Sekolah', 'Pembinaan hafalan Al-Qur’an dan adab harian.'],
            ['Seni Tari', 3, 'thursday', '14:00:00', '15:00:00', 'Aula Sekolah', 'Latihan gerak, ritme, dan ekspresi seni daerah.'],
        ];
        $clubs = collect($definitions)->map(function (array $definition) use ($teachers): Extracurricular {
            [$name, $teacherIndex, $day, $start, $end, $location, $description] = $definition;

            return Extracurricular::query()->updateOrCreate(
                ['name' => $name],
                [
                    'teacher_id' => $teachers[$teacherIndex]->id,
                    'description' => $description,
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'location' => $location,
                    'capacity' => 30,
                    'status' => 'active',
                ],
            );
        })->values();

        foreach ($families as $family) {
            $clubIndexes = [$family['index'] % $clubs->count()];

            if ($family['index'] % 3 === 0) {
                $clubIndexes[] = ($family['index'] + 1) % $clubs->count();
            }

            foreach (array_unique($clubIndexes) as $clubIndex) {
                ExtracurricularEnrollment::query()->updateOrCreate(
                    [
                        'extracurricular_id' => $clubs[$clubIndex]->id,
                        'student_id' => $family['student']->id,
                    ],
                    [
                        'joined_at' => $periodStart,
                        'status' => 'active',
                    ],
                );
            }
        }

        foreach ($clubs as $clubIndex => $club) {
            foreach ([10, 17, 24] as $sessionIndex => $dayOffset) {
                $session = ExtracurricularSession::query()->firstOrCreate(
                    [
                        'extracurricular_id' => $club->id,
                        'session_date' => $periodStart->addDays($dayOffset),
                        'title' => 'Pertemuan '.($sessionIndex + 1).' - '.$club->name,
                    ],
                    [
                        'teacher_id' => $club->teacher_id,
                        'start_time' => $club->start_time,
                        'end_time' => $club->end_time,
                        'material' => 'Latihan dasar, penguatan keterampilan, dan refleksi kegiatan.',
                        'notes' => 'Kegiatan berjalan tertib dan seluruh perlengkapan tersedia.',
                        'coach_attendance_status' => 'present',
                        'coach_notes' => 'Pelatih hadir tepat waktu.',
                    ],
                );
                $session->syncEnrolledStudents();

                foreach ($club->activeEnrollments()->get() as $enrollment) {
                    $status = (($enrollment->student_id + $sessionIndex + $clubIndex) % 17) === 0
                        ? 'permission'
                        : 'present';
                    ExtracurricularAttendance::query()->updateOrCreate(
                        [
                            'extracurricular_session_id' => $session->id,
                            'student_id' => $enrollment->student_id,
                        ],
                        [
                            'status' => $status,
                            'notes' => $status === 'present' ? 'Mengikuti kegiatan dengan baik.' : 'Izin dari orang tua.',
                        ],
                    );
                }
            }

            foreach ($club->activeEnrollments()->get() as $enrollment) {
                ExtracurricularScore::query()->updateOrCreate(
                    [
                        'extracurricular_id' => $club->id,
                        'student_id' => $enrollment->student_id,
                        'title' => 'Penilaian Awal Semester',
                        'assessed_at' => $periodStart->addDays(25),
                    ],
                    [
                        'recorded_by' => $club->teacher?->user_id,
                        'score' => 76 + (($enrollment->student_id + $clubIndex) % 20),
                        'notes' => 'Menunjukkan partisipasi, disiplin, dan perkembangan keterampilan yang baik.',
                    ],
                );
            }
        }
    }

    private function upsertUser(
        string $email,
        string $name,
        string $phone,
        string $jobTitle,
        Role $role,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::DEMO_PASSWORD,
                'phone' => $phone,
                'job_title' => $jobTitle,
                'status' => 'active',
                'must_change_password' => false,
            ],
        );
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * Simpan pengajuan tanpa event domain karena presensi dan notifikasi demo
     * disinkronkan secara eksplisit oleh seeder ini.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    private function upsertParentSubmission(array $attributes, array $values): ParentSubmission
    {
        return ParentSubmission::withoutEvents(
            fn (): ParentSubmission => ParentSubmission::query()->updateOrCreate($attributes, $values),
        );
    }

    private function attendanceKey(int $studentId, CarbonImmutable $date): string
    {
        return $studentId.'|'.$date->toDateString();
    }

    private function printSummary(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('Data demo SD Darusalam siap digunakan.');
        $this->command->table(
            ['Data', 'Jumlah'],
            [
                ['Siswa demo', Student::query()->where('nis', 'like', 'SDD-%')->count()],
                ['Guru', Teacher::query()->count()],
                ['Kelas', SchoolClass::query()->count()],
                ['Presensi', AttendanceRecord::query()->count()],
                ['Laporan sekolah', SchoolActivity::query()->count()],
                ['Laporan rumah', HomeActivity::query()->count()],
                ['Utas diskusi', ActivityComment::query()->whereNull('parent_id')->count()],
                ['Agenda kegiatan', Schedule::query()->count()],
                ['Ekstrakurikuler', Extracurricular::query()->count()],
                ['Permintaan reset sandi', PasswordResetRequest::query()->count()],
            ],
        );
        $this->command->line('Semua akun demo menggunakan kata sandi: password');
    }
}
