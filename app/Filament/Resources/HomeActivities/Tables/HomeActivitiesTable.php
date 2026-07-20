<?php

namespace App\Filament\Resources\HomeActivities\Tables;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Models\HomeActivity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeActivitiesTable
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
                TextColumn::make('student.parent.user.name')
                    ->label('Orang Tua')
                    ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
                    ->searchable(),
                TextColumn::make('activity_date')
                    ->label('Tanggal Aktivitas')
                    ->date()
                    ->sortable(),
                TextColumn::make('activity_category_summary')
                    ->label('Kategori Aktivitas')
                    ->wrap(),
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
                    ->visible(fn (HomeActivity $record): bool => HomeActivityResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => HomeActivityResource::canDeleteAny()),
                ]),
            ]);
    }
}
