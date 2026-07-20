<?php

namespace App\Filament\Resources\UserNotifications\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penerima')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Penerima'),
                        TextEntry::make('creator.name')
                            ->label('Dikirim Oleh')
                            ->placeholder('Sistem'),
                        IconEntry::make('is_read')
                            ->label('Sudah Dibaca')
                            ->boolean(),
                    ]),
                Section::make('Isi Notifikasi')
                    ->icon(Heroicon::OutlinedBell)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul'),
                        TextEntry::make('message')
                            ->label('Pesan'),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dikirim Pada')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}
