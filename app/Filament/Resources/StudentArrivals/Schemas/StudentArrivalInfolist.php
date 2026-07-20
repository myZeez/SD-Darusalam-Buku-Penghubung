<?php

namespace App\Filament\Resources\StudentArrivals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StudentArrivalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kedatangan Siswa')
                ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('student.name')->label('Nama Siswa'),
                    TextEntry::make('student.nis')->label('NIS')->copyable(),
                    TextEntry::make('schoolClass.name')->label('Kelas')->badge()->placeholder('Belum ada kelas'),
                    TextEntry::make('arrival_date')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('arrival_time')->label('Waktu Datang')->time('H:i'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => $state === 'late' ? 'danger' : 'success')
                        ->formatStateUsing(fn (string $state): string => $state === 'late' ? 'Terlambat' : 'Tepat Waktu'),
                    TextEntry::make('recorder.name')->label('Dicatat Oleh')->placeholder('Sistem'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('Tidak ada catatan')->columnSpanFull(),
                ]),
            Section::make('Informasi Sistem')
                ->icon(Heroicon::OutlinedClock)
                ->columns(['md' => 2])
                ->schema([
                    TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d M Y, H:i'),
                    TextEntry::make('updated_at')->label('Diperbarui Pada')->dateTime('d M Y, H:i'),
                ]),
        ]);
    }
}
