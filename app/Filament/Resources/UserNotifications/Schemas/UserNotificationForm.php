<?php

namespace App\Filament\Resources\UserNotifications\Schemas;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class UserNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penerima')
                    ->description('Tentukan pengguna dan status awal notifikasi.')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => UserNotificationResource::scopeRecipientQuery($query),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} - {$record->email}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Penerima')
                            ->helperText(fn (): string => auth()->user()?->hasRole('guru')
                                ? 'Hanya siswa dan orang tua dari kelas yang Anda ampu.'
                                : 'Admin dapat memilih seluruh pengguna.'),
                    ]),
                Section::make('Isi Notifikasi')
                    ->icon(Heroicon::OutlinedBell)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->maxLength(255)
                            ->required(),
                        Textarea::make('message')
                            ->label('Pesan')
                            ->rows(6)
                            ->required(),
                    ]),
            ]);
    }
}
