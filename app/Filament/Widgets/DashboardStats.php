<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\TeacherAttendance;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Models\AttendanceRecord;
use App\Models\ExtracurricularEnrollment;
use App\Models\HomeActivity;
use App\Models\Schedule;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentArrival;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->isAdmin()) {
            $activeStudents = Student::query()->where('status', 'active')->count();
            $arrivalsToday = StudentArrival::query()->whereDate('arrival_date', today())->count();

            return [
                Stat::make('Total Siswa Aktif', $activeStudents)
                    ->icon('gmdi-badge-o'),
                Stat::make('Total Kelas', SchoolClass::count())
                    ->icon('gmdi-class-o'),
                Stat::make('Check-in Hari Ini', $arrivalsToday)
                    ->icon('gmdi-login-o')
                    ->url(StudentArrivalResource::getUrl()),
                Stat::make('Terlambat Hari Ini', StudentArrival::query()
                    ->whereDate('arrival_date', today())
                    ->where('status', 'late')
                    ->count())
                    ->icon('gmdi-alarm-o')
                    ->url(StudentArrivalResource::getUrl()),
                Stat::make('Hadir Hari Ini', AttendanceRecord::query()
                    ->whereDate('attendance_date', today())
                    ->where('status', 'present')
                    ->count())
                    ->icon('gmdi-fact-check-o')
                    ->url(AttendanceRecordResource::getUrl()),
                Stat::make('Belum Check-in', max(0, $activeStudents - $arrivalsToday))
                    ->icon('gmdi-hourglass-empty-o'),
                Stat::make('Jadwal Hari Ini', Schedule::whereDate('activity_date', today())->count())
                    ->icon('gmdi-calendar-month-o'),
            ];
        }

        if ($user->hasRole('loket')) {
            $activeStudents = Student::query()->where('status', 'active')->count();
            $arrivalsToday = StudentArrival::query()->whereDate('arrival_date', today());
            $arrivalCount = (clone $arrivalsToday)->count();

            return [
                Stat::make('Check-in Hari Ini', $arrivalCount)
                    ->icon('gmdi-login-o')
                    ->url(StudentArrivalResource::getUrl()),
                Stat::make('Tepat Waktu', (clone $arrivalsToday)->where('status', 'on_time')->count())
                    ->icon('gmdi-check-circle-o'),
                Stat::make('Terlambat', (clone $arrivalsToday)->where('status', 'late')->count())
                    ->icon('gmdi-alarm-o'),
                Stat::make('Belum Check-in', max(0, $activeStudents - $arrivalCount))
                    ->icon('gmdi-hourglass-empty-o'),
            ];
        }

        $studentIds = fn () => $user->accessibleStudents()->select('students.id');

        if ($user->hasRole('guru')) {
            $managedStudentIds = fn () => $user->managedStudents()->select('students.id');
            $studentCount = $user->managedStudents()->where('status', 'active')->count();
            $attendanceCount = AttendanceRecord::query()
                ->whereIn('student_id', $managedStudentIds())
                ->whereDate('attendance_date', today())
                ->count();

            return [
                Stat::make('Total Siswa', $studentCount),
                Stat::make('Aktivitas Sekolah Hari Ini', SchoolActivity::whereIn('student_id', $managedStudentIds())->whereDate('activity_date', today())->count()),
                Stat::make('Aktivitas Rumah Baru', HomeActivity::whereIn('student_id', $managedStudentIds())->whereDate('created_at', today())->count()),
                Stat::make('Jadwal Hari Ini', $this->getAccessibleScheduleCount($user)),
                Stat::make('Presensi Kelas Hari Ini', "{$attendanceCount} / {$studentCount}")
                    ->description($studentCount === $attendanceCount ? 'Seluruh siswa sudah dicatat' : 'Lengkapi presensi kelas')
                    ->icon('gmdi-fact-check-o')
                    ->url(TeacherAttendance::getUrl()),
            ];
        }

        if ($user->hasRole('orang_tua')) {
            $studentCount = $user->accessibleStudents()->count();
            $attendanceToday = AttendanceRecord::query()
                ->whereIn('student_id', $studentIds())
                ->whereDate('attendance_date', today());
            $presentToday = (clone $attendanceToday)->where('status', 'present')->count();
            $lateToday = (clone $attendanceToday)->where('is_late', true)->count();

            return [
                Stat::make('Jumlah Anak', $studentCount),
                Stat::make('Presensi Hari Ini', "{$presentToday} / {$studentCount} hadir")
                    ->description($lateToday > 0 ? "{$lateToday} anak tercatat terlambat" : null)
                    ->icon('gmdi-fact-check-o')
                    ->url(AttendanceRecordResource::getUrl()),
                Stat::make('Aktivitas Rumah Hari Ini', HomeActivity::whereIn('student_id', $studentIds())->whereDate('activity_date', today())->count()),
                Stat::make('Ekstrakurikuler Diikuti', ExtracurricularEnrollment::query()
                    ->whereIn('student_id', $studentIds())
                    ->where('status', 'active')
                    ->count())
                    ->icon('gmdi-sports-soccer-o'),
                Stat::make('Jadwal Hari Ini', $this->getAccessibleScheduleCount($user)),
            ];
        }

        $attendance = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds())
            ->whereDate('attendance_date', today())
            ->first();

        return [
            Stat::make('Presensi Hari Ini', $this->attendanceLabel($attendance?->status))
                ->description($attendance?->is_late ? 'Tercatat datang terlambat' : null)
                ->icon('gmdi-fact-check-o'),
            Stat::make('Ekstrakurikuler Diikuti', ExtracurricularEnrollment::query()
                ->whereIn('student_id', $studentIds())
                ->where('status', 'active')
                ->count())
                ->icon('gmdi-sports-soccer-o'),
        ];
    }

    private function getAccessibleScheduleCount(User $user): int
    {
        return Schedule::query()
            ->whereDate('activity_date', today())
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereNull('class_id')
                    ->orWhereIn('class_id', ($user->hasRole('guru') ? $user->managedClasses() : $user->accessibleClasses())->select('classes.id'));
            })
            ->count();
    }

    private function attendanceLabel(?string $status): string
    {
        return match ($status) {
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
            default => 'Belum Dicatat',
        };
    }
}
