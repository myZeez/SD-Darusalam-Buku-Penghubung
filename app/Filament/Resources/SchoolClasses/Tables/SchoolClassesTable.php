<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['teacher.user'])->withCount([
                'students' => fn (Builder $query): Builder => $query->where('status', 'active'),
            ]))
            ->columns([
                ViewColumn::make('ringkasan_kelas')
                    ->label('Kelas')
                    ->view('filament.tables.columns.school-class-card'),
                TextColumn::make('name')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('teacher.user.name')
                    ->searchable()
                    ->hidden(),
            ])
            ->recordUrl(null)
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
