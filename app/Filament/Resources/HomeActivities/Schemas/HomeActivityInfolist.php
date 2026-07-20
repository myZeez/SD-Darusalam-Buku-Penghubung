<?php

namespace App\Filament\Resources\HomeActivities\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HomeActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Aktivitas')
                    ->icon(Heroicon::OutlinedHome)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('student.name')
                            ->label('Siswa'),
                        TextEntry::make('student.parent.user.name')
                            ->label('Orang Tua')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('activity_date')
                            ->label('Tanggal Aktivitas')
                            ->date('d M Y'),
                    ]),
                Section::make('Aktivitas Siswa')
                    ->description('Aktivitas dikelompokkan berdasarkan kategori yang dicatat orang tua.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('activity_groups')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record): array => $record->resolvedActivityGroups())
                            ->view('filament.infolists.entries.activity-groups'),
                    ]),
                Section::make('Catatan dan Dokumentasi')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 2,
                    ])
                    ->schema([
                        TextEntry::make('note')
                            ->label('Catatan Orang Tua')
                            ->placeholder('Belum ada catatan'),
                        ImageEntry::make('photo')
                            ->label('Foto Aktivitas')
                            ->imageSize(160)
                            ->square(),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}
