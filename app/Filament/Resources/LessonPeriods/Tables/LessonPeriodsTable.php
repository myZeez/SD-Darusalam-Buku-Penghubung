<?php

namespace App\Filament\Resources\LessonPeriods\Tables;

use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use App\Models\LessonPeriod;
use App\Support\AcademicCalendar;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('sequence')->label('Urutan')->badge()->sortable(),
                TextColumn::make('name')->label('Periode')->searchable()->weight('bold'),
                TextColumn::make('start_time')->label('Mulai')->time('H:i')->sortable(),
                TextColumn::make('end_time')->label('Selesai')->time('H:i')->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'lesson' ? 'info' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => AcademicCalendar::PERIOD_TYPES[$state] ?? (string) $state),
                TextColumn::make('academicPeriod.label')->label('Periode Akademik')->wrap(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('academic_period_id')->label('Periode Akademik')->relationship('academicPeriod', 'academic_year'),
                SelectFilter::make('type')->label('Jenis')->options(AcademicCalendar::PERIOD_TYPES),
            ])
            ->defaultSort('sequence')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (LessonPeriod $record): bool => LessonPeriodResource::canEdit($record)),
            ])
            ->emptyStateHeading('Belum ada periode pelajaran')
            ->emptyStateDescription('Susun rentang jam sekolah sebelum menempatkan jadwal pelajaran.')
            ->emptyStateIcon('gmdi-schedule-o');
    }
}
