<?php

namespace App\Filament\Resources\AttendanceRecords\Tables;

use App\Exports\AttendanceRecordsExport;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'student',
                'schoolClass',
                'recorder',
            ]))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->description(fn (AttendanceRecord $record): ?string => auth()->user()?->hasRole('orang_tua')
                        ? null
                        : ($record->student?->nis ?? '-'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->badge()
                    ->placeholder('Belum ada kelas'),
                TextColumn::make('attendance_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Kehadiran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'sick' => 'warning',
                        'permission' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'absent' => 'Alpa',
                        default => $state,
                    }),
                IconColumn::make('is_late')
                    ->label('Terlambat')
                    ->boolean(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'arrival' => 'Loket',
                        'parent_submission' => 'Pengajuan Orang Tua',
                        'manual' => 'Manual',
                        'teacher' => 'Guru',
                        default => $state,
                    })
                    ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                TextColumn::make('recorder.name')
                    ->label('Dikonfirmasi Oleh')
                    ->placeholder('Belum dikonfirmasi')
                    ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'present' => 'Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'absent' => 'Alpa',
                    ]),
                Filter::make('attendance_date')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '<=', $date))),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->headerActions([
                Action::make('export')
                    ->label('Unduh Excel')
                    ->icon('gmdi-download-o')
                    ->visible(fn (): bool => auth()->user()?->can('export reports') ?? false)
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(today()),
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->placeholder('Semua kelas'),
                    ])
                    ->action(fn (array $data) => Excel::download(
                        new AttendanceRecordsExport($data),
                        'rekap-presensi-'.now()->format('Ymd-His').'.xlsx',
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (AttendanceRecord $record): bool => AttendanceRecordResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => AttendanceRecordResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada presensi')
            ->emptyStateDescription('Check-in loket atau pencatatan manual akan membentuk data presensi.');
    }
}
