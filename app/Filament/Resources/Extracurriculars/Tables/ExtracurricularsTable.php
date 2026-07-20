<?php

namespace App\Filament\Resources\Extracurriculars\Tables;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Models\Extracurricular;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExtracurricularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('name')
                    ->label('Ekstrakurikuler')
                    ->description(fn (Extracurricular $record): string => $record->location ?? 'Lokasi belum ditentukan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.user.name')
                    ->label('Guru Pembina')
                    ->placeholder('Belum ditentukan'),
                TextColumn::make('monitored_students')
                    ->label('Anak yang Mengikuti')
                    ->state(fn (Extracurricular $record): string => $record->monitoredEnrollments(auth()->user())
                        ->pluck('student.name')
                        ->filter()
                        ->implode(', ') ?: '-')
                    ->description(fn (Extracurricular $record): string => $record->monitoredEnrollments(auth()->user())
                        ->pluck('student.class.name')
                        ->filter()
                        ->unique()
                        ->implode(', ') ?: 'Kelas belum ditentukan')
                    ->wrap()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false),
                TextColumn::make('day_of_week')
                    ->label('Jadwal')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'monday' => 'Senin',
                        'tuesday' => 'Selasa',
                        'wednesday' => 'Rabu',
                        'thursday' => 'Kamis',
                        'friday' => 'Jumat',
                        'saturday' => 'Sabtu',
                        default => 'Belum ditentukan',
                    })
                    ->description(fn (Extracurricular $record): ?string => filled($record->start_time)
                        ? substr($record->start_time, 0, 5).' - '.substr($record->end_time ?? $record->start_time, 0, 5)
                        : null),
                TextColumn::make('active_enrollments_count')
                    ->label('Peserta Aktif')
                    ->counts('activeEnrollments')
                    ->suffix(fn (Extracurricular $record): string => ' / '.$record->capacity)
                    ->badge()
                    ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false),
                TextColumn::make('sessions_count')
                    ->label('Sesi')
                    ->counts('sessions')
                    ->suffix(' sesi')
                    ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false),
                TextColumn::make('attendance_monitoring')
                    ->label('Kehadiran')
                    ->state(function (Extracurricular $record): string {
                        $overview = $record->attendanceOverviewFor(auth()->user());

                        return $overview['total'] > 0
                            ? "{$overview['present']} dari {$overview['total']} pertemuan hadir"
                            : 'Belum ada absensi';
                    })
                    ->description(function (Extracurricular $record): ?string {
                        $overview = $record->attendanceOverviewFor(auth()->user());

                        return $overview['missed'] > 0 ? "{$overview['missed']} kali tidak hadir" : null;
                    })
                    ->badge()
                    ->color(function (Extracurricular $record): string {
                        $overview = $record->attendanceOverviewFor(auth()->user());

                        return $overview['total'] === 0 ? 'gray' : ($overview['missed'] === 0 ? 'success' : 'warning');
                    })
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Tidak Aktif']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Extracurricular $record): bool => ExtracurricularResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ExtracurricularResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada ekstrakurikuler')
            ->emptyStateDescription(auth()->user()?->isAdmin()
                ? 'Tambahkan kegiatan ekstrakurikuler beserta guru pembina dan jadwal rutinnya.'
                : 'Ekstrakurikuler yang diikuti siswa akan tampil di halaman ini.');
    }
}
