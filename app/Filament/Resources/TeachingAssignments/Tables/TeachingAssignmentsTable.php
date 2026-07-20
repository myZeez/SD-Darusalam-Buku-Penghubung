<?php

namespace App\Filament\Resources\TeachingAssignments\Tables;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\TeachingAssignment;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeachingAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'academicPeriod',
                'teacher.user',
                'schoolClass',
                'subject',
            ]))
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->description(fn (TeachingAssignment $record): ?string => $record->teacher?->nip)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->description(fn (TeachingAssignment $record): ?string => $record->subject?->code)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schoolClass.name')->label('Kelas')->badge()->searchable()->sortable(),
                TextColumn::make('academicPeriod.label')->label('Periode')->wrap(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('academic_period_id')->label('Periode')->relationship('academicPeriod', 'academic_year'),
                SelectFilter::make('class_id')->label('Kelas')->relationship('schoolClass', 'name'),
                SelectFilter::make('subject_id')->label('Mata Pelajaran')->relationship('subject', 'name'),
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (TeachingAssignment $record): bool => TeachingAssignmentResource::canEdit($record)),
            ])
            ->emptyStateHeading('Belum ada penugasan Guru')
            ->emptyStateDescription('Hubungkan Guru, kelas, dan mata pelajaran sebelum menyusun jadwal mingguan.')
            ->emptyStateIcon('gmdi-assignment-ind-o');
    }
}
