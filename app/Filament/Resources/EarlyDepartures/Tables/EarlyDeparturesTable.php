<?php

namespace App\Filament\Resources\EarlyDepartures\Tables;

use App\Filament\Resources\EarlyDepartures\EarlyDepartureResource;
use App\Models\EarlyDeparture;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EarlyDeparturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['student', 'schoolClass', 'parentSubmission', 'recorder']))
            ->columns([
                TextColumn::make('student.name')->label('Siswa')->searchable()->sortable(),
                TextColumn::make('schoolClass.name')->label('Kelas')->badge()->placeholder('Belum ada kelas'),
                TextColumn::make('departure_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('departure_time')->label('Jam Pulang')->time('H:i'),
                TextColumn::make('parent_submission_id')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (?int $state): string => $state ? 'info' : 'warning')
                    ->formatStateUsing(fn (?int $state): string => $state ? 'Izin Orang Tua' : 'Catatan Wali Kelas'),
                TextColumn::make('notes')->label('Catatan')->wrap()->limit(80),
                TextColumn::make('recorder.name')->label('Dicatat Oleh')->placeholder('Sistem')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('class_id')->label('Kelas')->relationship('schoolClass', 'name')->searchable()->preload(),
            ])
            ->defaultSort('departure_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EarlyDeparture $record): bool => EarlyDepartureResource::canEdit($record)),
                DeleteAction::make()->visible(fn (EarlyDeparture $record): bool => EarlyDepartureResource::canDelete($record)),
            ]);
    }
}
