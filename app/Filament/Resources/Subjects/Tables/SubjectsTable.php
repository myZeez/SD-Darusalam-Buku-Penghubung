<?php

namespace App\Filament\Resources\Subjects\Tables;

use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Subject;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('teachingAssignments'))
            ->columns([
                TextColumn::make('code')->label('Kode')->badge()->searchable()->sortable(),
                TextColumn::make('name')->label('Mata Pelajaran')->searchable()->sortable()->weight('bold'),
                TextColumn::make('teaching_assignments_count')->label('Penugasan')->badge()->color('info'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Subject $record): bool => SubjectResource::canEdit($record)),
            ])
            ->emptyStateHeading('Belum ada mata pelajaran')
            ->emptyStateDescription('Tambahkan mata pelajaran sebelum membuat penugasan Guru.')
            ->emptyStateIcon('gmdi-menu-book-o');
    }
}
