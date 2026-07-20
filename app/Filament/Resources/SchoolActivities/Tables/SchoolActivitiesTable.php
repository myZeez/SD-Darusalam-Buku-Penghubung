<?php

namespace App\Filament\Resources\SchoolActivities\Tables;

use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\SchoolActivity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                //
            ])
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
            ]);
    }
}
