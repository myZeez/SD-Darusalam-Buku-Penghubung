<?php

namespace App\Filament\Resources\SchoolSettings\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SchoolSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Sekolah')
                ->icon(Heroicon::OutlinedBuildingLibrary)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    ImageEntry::make('logo')
                        ->label('Logo Sekolah')
                        ->imageSize(112),
                    TextEntry::make('school_name')->label('Nama Sekolah'),
                    TextEntry::make('npsn')->label('NPSN')->placeholder('Belum diisi'),
                    TextEntry::make('principal_name')->label('Kepala Sekolah')->placeholder('Belum diisi'),
                    TextEntry::make('phone')->label('Telepon')->placeholder('Belum diisi'),
                    TextEntry::make('email')->label('Email')->placeholder('Belum diisi'),
                    TextEntry::make('address')->label('Alamat')->placeholder('Belum diisi')->columnSpanFull(),
                ]),
            Section::make('Aturan Kehadiran')
                ->icon(Heroicon::OutlinedClock)
                ->columns(['md' => 3])
                ->schema([
                    TextEntry::make('school_start_time')->label('Jam Masuk')->time('H:i'),
                    TextEntry::make('late_tolerance_minutes')->label('Toleransi')->suffix(' menit'),
                    TextEntry::make('timezone')->label('Zona Waktu'),
                    TextEntry::make('school_days')
                        ->label('Hari Aktif')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'monday' => 'Senin',
                            'tuesday' => 'Selasa',
                            'wednesday' => 'Rabu',
                            'thursday' => 'Kamis',
                            'friday' => 'Jumat',
                            'saturday' => 'Sabtu',
                            default => $state,
                        }),
                ]),
        ]);
    }
}
