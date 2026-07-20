<?php

namespace App\Filament\Resources\Extracurriculars\Schemas;

use App\Models\Extracurricular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ExtracurricularInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->description('Identitas kegiatan dan penanggung jawab ekstrakurikuler.')
                ->icon(Heroicon::OutlinedTrophy)
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('name')->label('Nama'),
                    TextEntry::make('teacher.user.name')->label('Guru Pembina')->placeholder('Belum ditentukan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                        ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Belum ada deskripsi')
                        ->columnSpanFull(),
                ]),
            Section::make('Pelaksanaan dan Capaian')
                ->description('Jadwal rutin, kapasitas, jumlah sesi, dan data penilaian.')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columnSpanFull()
                ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('day_of_week')
                        ->label('Hari Latihan')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'monday' => 'Senin',
                            'tuesday' => 'Selasa',
                            'wednesday' => 'Rabu',
                            'thursday' => 'Kamis',
                            'friday' => 'Jumat',
                            'saturday' => 'Sabtu',
                            default => 'Belum ditentukan',
                        }),
                    TextEntry::make('schedule_time')
                        ->label('Waktu')
                        ->state(fn (Extracurricular $record): string => filled($record->start_time)
                            ? substr($record->start_time, 0, 5).' - '.substr($record->end_time ?? $record->start_time, 0, 5)
                            : 'Belum ditentukan'),
                    TextEntry::make('location')->label('Lokasi')->placeholder('Belum ditentukan'),
                    TextEntry::make('participant_count')
                        ->label('Peserta Aktif')
                        ->state(fn (Extracurricular $record): int => $record->activeEnrollments()->count())
                        ->suffix(fn (Extracurricular $record): string => ' / '.$record->capacity.' siswa'),
                    TextEntry::make('sessions_count')
                        ->label('Sesi Tercatat')
                        ->state(fn (Extracurricular $record): int => $record->sessions()->count())
                        ->suffix(' sesi'),
                    TextEntry::make('scores_count')
                        ->label('Penilaian Tercatat')
                        ->state(fn (Extracurricular $record): int => $record->scores()->count())
                        ->suffix(' nilai'),
                ]),
            Section::make('Pemantauan Anak')
                ->description('Ringkasan kegiatan dan kehadiran anak pada ekstrakurikuler ini.')
                ->icon(Heroicon::OutlinedEye)
                ->columnSpanFull()
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('monitored_students')
                        ->label('Anak yang Mengikuti')
                        ->state(fn (Extracurricular $record): string => $record->monitoredEnrollments(auth()->user())
                            ->pluck('student.name')
                            ->filter()
                            ->implode(', ') ?: '-'),
                    TextEntry::make('monitored_classes')
                        ->label('Kelas')
                        ->state(fn (Extracurricular $record): string => $record->monitoredEnrollments(auth()->user())
                            ->pluck('student.class.name')
                            ->filter()
                            ->unique()
                            ->implode(', ') ?: 'Belum ada kelas'),
                    TextEntry::make('monitoring_schedule')
                        ->label('Jadwal Rutin')
                        ->state(fn (Extracurricular $record): string => sprintf(
                            '%s, %s',
                            match ($record->day_of_week) {
                                'monday' => 'Senin',
                                'tuesday' => 'Selasa',
                                'wednesday' => 'Rabu',
                                'thursday' => 'Kamis',
                                'friday' => 'Jumat',
                                'saturday' => 'Sabtu',
                                default => 'Hari belum ditentukan',
                            },
                            filled($record->start_time)
                                ? substr($record->start_time, 0, 5).' - '.substr($record->end_time ?? $record->start_time, 0, 5)
                                : 'waktu belum ditentukan',
                        )),
                    TextEntry::make('location')
                        ->label('Lokasi')
                        ->placeholder('Belum ditentukan'),
                    TextEntry::make('attendance_summary')
                        ->label('Kehadiran')
                        ->state(function (Extracurricular $record): string {
                            $overview = $record->attendanceOverviewFor(auth()->user());

                            return $overview['total'] > 0
                                ? "Hadir {$overview['present']} dari {$overview['total']} pertemuan"
                                : 'Belum ada absensi';
                        })
                        ->badge()
                        ->color(function (Extracurricular $record): string {
                            $overview = $record->attendanceOverviewFor(auth()->user());

                            return $overview['total'] === 0 ? 'gray' : ($overview['missed'] === 0 ? 'success' : 'warning');
                        }),
                    TextEntry::make('latest_attendance')
                        ->label('Kehadiran Terakhir')
                        ->state(function (Extracurricular $record): string {
                            $overview = $record->attendanceOverviewFor(auth()->user());
                            $status = match ($overview['latest_status']) {
                                'present' => 'Hadir',
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'absent' => 'Alpa',
                                default => null,
                            };

                            return $status && $overview['latest_date']
                                ? $status.' pada '.date('d M Y', strtotime($overview['latest_date']))
                                : 'Belum ada catatan';
                        }),
                ]),
        ]);
    }
}
