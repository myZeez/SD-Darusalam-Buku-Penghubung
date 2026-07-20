<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AttendanceRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Presensi Siswa')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('student.name')->label('Nama Siswa'),
                    TextEntry::make('student.nis')
                        ->label('NIS')
                        ->copyable()
                        ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                    TextEntry::make('schoolClass.name')->label('Kelas')->badge()->placeholder('Belum ada kelas'),
                    TextEntry::make('attendance_date')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('status')
                        ->label('Status Kehadiran')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'present' => 'success',
                            'sick' => 'warning',
                            'permission' => 'info',
                            'absent' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'present' => 'Hadir',
                            'sick' => 'Sakit',
                            'permission' => 'Izin',
                            'absent' => 'Alpa',
                            default => $state,
                        }),
                    IconEntry::make('is_late')->label('Datang Terlambat')->boolean(),
                    TextEntry::make('source')
                        ->label('Sumber Data')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'arrival' => 'Check-in Loket',
                            'parent_submission' => 'Pengajuan Orang Tua',
                            'manual' => 'Pencatatan Manual',
                            'teacher' => 'Konfirmasi Guru',
                            default => $state,
                        })
                        ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                    TextEntry::make('recorder.name')
                        ->label('Dikonfirmasi Oleh')
                        ->placeholder('Belum dikonfirmasi')
                        ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                    TextEntry::make('verified_at')
                        ->label('Waktu Konfirmasi')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Belum dikonfirmasi')
                        ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                    TextEntry::make('notes')
                        ->label('Catatan')
                        ->placeholder('Tidak ada catatan')
                        ->columnSpanFull()
                        ->hidden(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                ]),
        ]);
    }
}
