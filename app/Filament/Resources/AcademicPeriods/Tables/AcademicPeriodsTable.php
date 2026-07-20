<?php

namespace App\Filament\Resources\AcademicPeriods\Tables;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use App\Support\AcademicCalendar;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcademicPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['classes', 'teachingAssignments']))
            ->columns([
                TextColumn::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('semester')
                    ->label('Semester')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AcademicCalendar::semesterLabel($state)),
                TextColumn::make('start_date')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('end_date')->label('Selesai')->date('d M Y')->sortable(),
                TextColumn::make('classes_count')->label('Kelas')->badge()->color('info'),
                TextColumn::make('teaching_assignments_count')->label('Penugasan')->badge()->color('gray'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (AcademicPeriod $record): bool => AcademicPeriodResource::canEdit($record)),
            ])
            ->emptyStateHeading('Belum ada periode akademik')
            ->emptyStateDescription('Buat tahun ajaran dan semester sebelum mengatur kelas serta jadwal pelajaran.')
            ->emptyStateIcon('gmdi-date-range-o');
    }
}
