<?php

namespace App\Filament\Resources\ParentProfiles\Tables;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Models\ParentProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParentProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('father_name')
                    ->label('Nama Ayah')
                    ->searchable(),
                TextColumn::make('mother_name')
                    ->label('Nama Ibu')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable(),
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
                    ->visible(fn (ParentProfile $record): bool => ParentProfileResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ParentProfileResource::canDeleteAny()),
                ]),
            ]);
    }
}
