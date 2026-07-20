<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Agenda')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('schoolClass.name')
                            ->label('Target')
                            ->badge()
                            ->placeholder('Seluruh Sekolah'),
                        TextEntry::make('title')
                            ->label('Judul Kegiatan'),
                        TextEntry::make('location')
                            ->label('Lokasi')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('activity_date')
                            ->label('Tanggal Kegiatan')
                            ->date('d M Y'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Belum ada deskripsi')
                            ->columnSpanFull(),
                    ]),
                Section::make('Waktu dan Persiapan')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('start_time')
                            ->label('Waktu Mulai')
                            ->time('H:i')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('end_time')
                            ->label('Waktu Selesai')
                            ->time('H:i')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('equipment')
                            ->label('Perlengkapan')
                            ->placeholder('Tidak ada perlengkapan khusus')
                            ->columnSpanFull(),
                    ]),
                Section::make('Konfirmasi Kehadiran Saya')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->columnSpanFull()
                    ->visible(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false)
                    ->columns(['md' => 2])
                    ->schema([
                        TextEntry::make('parent_response')
                            ->label('Status')
                            ->state(fn ($record): string => match ($record->responses()->where('user_id', auth()->id())->value('response')) {
                                'attending' => 'Saya Akan Datang',
                                'unavailable' => 'Belum Bisa Datang',
                                'reschedule' => 'Jadwalkan Ulang',
                                default => 'Belum Dikonfirmasi',
                            })
                            ->badge(),
                        TextEntry::make('parent_proposed_date')
                            ->label('Usulan Tanggal Baru')
                            ->state(fn ($record): string => $record->responses()->where('user_id', auth()->id())->value('proposed_date')
                                ? date('d M Y', strtotime($record->responses()->where('user_id', auth()->id())->value('proposed_date')))
                                : '-'),
                        TextEntry::make('parent_note')
                            ->label('Catatan')
                            ->state(fn ($record): string => $record->responses()->where('user_id', auth()->id())->value('note') ?: '-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('Sistem'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}
