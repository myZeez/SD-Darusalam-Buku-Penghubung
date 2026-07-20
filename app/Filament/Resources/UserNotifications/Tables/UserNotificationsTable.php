<?php

namespace App\Filament\Resources\UserNotifications\Tables;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Models\UserNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile(fn (): bool => auth()->user()?->hasAnyRole(['guru', 'orang_tua', 'siswa']) ?? false)
            ->columns([
                TextColumn::make('direction')
                    ->label('Arah')
                    ->state(fn (UserNotification $record): string => $record->user_id === auth()->id() ? 'incoming' : 'outgoing')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'incoming' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'incoming' ? 'Masuk' : 'Terkirim')
                    ->visible(fn (): bool => auth()->user()?->hasRole('guru') ?? false),
                TextColumn::make('user.name')
                    ->label('Penerima')
                    ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->description(fn (UserNotification $record): string => str($record->message)->limit(80))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Dikirim Oleh')
                    ->placeholder('Sistem')
                    ->toggleable(),
                IconColumn::make('is_read')
                    ->label('Sudah Dibaca')
                    ->boolean(),
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
                TernaryFilter::make('is_read')
                    ->label('Status Baca')
                    ->trueLabel('Sudah Dibaca')
                    ->falseLabel('Belum Dibaca')
                    ->placeholder('Semua Notifikasi'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('markAllRead')
                    ->label('Tandai Semua Dibaca')
                    ->icon('gmdi-done-all')
                    ->color('gray')
                    ->visible(fn (): bool => UserNotification::query()
                        ->where('user_id', auth()->id())
                        ->where('is_read', false)
                        ->exists())
                    ->action(fn () => UserNotification::query()
                        ->where('user_id', auth()->id())
                        ->where('is_read', false)
                        ->update(['is_read' => true])),
            ])
            ->recordActions([
                Action::make('markRead')
                    ->label('Tandai Dibaca')
                    ->icon('gmdi-mark-email-read-o')
                    ->color('gray')
                    ->visible(fn (UserNotification $record): bool => $record->user_id === auth()->id() && ! $record->is_read)
                    ->action(fn (UserNotification $record) => $record->update(['is_read' => true])),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (UserNotification $record): bool => UserNotificationResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => UserNotificationResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada notifikasi')
            ->emptyStateDescription('Pesan dari sistem, orang tua, atau guru akan muncul di sini.');
    }
}
