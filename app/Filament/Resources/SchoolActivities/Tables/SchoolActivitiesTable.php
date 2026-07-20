<?php

namespace App\Filament\Resources\SchoolActivities\Tables;

use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\SchoolActivity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->searchable(),
                TextColumn::make('activity_date')
                    ->label('Tanggal Aktivitas')
                    ->date()
                    ->sortable(),
                TextColumn::make('attendance')
                    ->label('Kehadiran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'absent' => 'Alpa',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('activity_category_summary')
                    ->label('Kategori Aktivitas')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->options(fn (): array => auth()->user()?->accessibleStudents()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all() ?? [])
                    ->searchable()
                    ->preload(),
                Filter::make('class_id')
                    ->label('Kelas')
                    ->form([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => auth()->user()?->accessibleClasses()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all() ?? [])
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['class_id'] ?? null, fn (Builder $query, int $classId): Builder => $query
                            ->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery->where('class_id', $classId)))),
                SelectFilter::make('attendance')
                    ->label('Kehadiran')
                    ->options([
                        'present' => 'Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'absent' => 'Alpa',
                    ]),
                Filter::make('activity_date')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '<=', $date))),
            ])
            ->defaultSort('activity_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (SchoolActivity $record): bool => SchoolActivityResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => SchoolActivityResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada laporan sekolah')
            ->emptyStateDescription('Gunakan tombol Buat Laporan Harian untuk mencatat perkembangan siswa.');
    }
}
