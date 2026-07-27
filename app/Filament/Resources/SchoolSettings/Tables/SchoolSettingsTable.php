<?php

namespace App\Filament\Resources\SchoolSettings\Tables;

use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use App\Models\SchoolSetting;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_name')
                    ->label('Nama Sekolah')
                    ->searchable(),
                TextColumn::make('school_start_time')
                    ->label('Jam Masuk')
                    ->time('H:i'),
                TextColumn::make('late_tolerance_minutes')
                    ->label('Toleransi')
                    ->suffix(' menit'),
                TextColumn::make('principal_name')
                    ->label('Kepala Sekolah')
                    ->placeholder('Belum diisi'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (SchoolSetting $record): bool => SchoolSettingResource::canEdit($record)),
            ])
            ->toolbarActions([]);
    }
}
