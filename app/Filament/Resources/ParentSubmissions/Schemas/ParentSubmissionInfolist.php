<?php

namespace App\Filament\Resources\ParentSubmissions\Schemas;

use App\Models\ParentSubmission;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ParentSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan Orang Tua')
                ->icon(Heroicon::OutlinedDocumentText)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('student.name')->label('Nama Siswa'),
                    TextEntry::make('student.class.name')->label('Kelas')->badge()->placeholder('Belum ada kelas'),
                    TextEntry::make('parent.user.name')->label('Orang Tua')->placeholder('Belum terhubung'),
                    TextEntry::make('type')
                        ->label('Jenis Laporan')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'sick' => 'Sakit',
                            'permission' => 'Izin',
                            'family' => 'Keperluan Keluarga',
                            'late' => 'Pemberitahuan Terlambat',
                            'home_report' => 'Laporan Rumah',
                            'other' => 'Lainnya',
                            default => $state,
                        }),
                    TextEntry::make('start_date')->label('Mulai Tanggal')->date('d M Y'),
                    TextEntry::make('end_date')->label('Sampai Tanggal')->date('d M Y'),
                    TextEntry::make('title')->label('Judul')->columnSpanFull(),
                    TextEntry::make('description')->label('Keterangan')->columnSpanFull(),
                    TextEntry::make('attachment')
                        ->label('Lampiran')
                        ->formatStateUsing(fn (?string $state): string => $state ? 'Buka Lampiran' : 'Tidak ada lampiran')
                        ->url(fn (ParentSubmission $record): ?string => $record->attachment ? Storage::url($record->attachment) : null)
                        ->openUrlInNewTab(),
                ]),
            Section::make('Tinjauan Sekolah')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->columns(['md' => 2])
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => $state,
                        }),
                    TextEntry::make('reviewer.name')->label('Ditinjau Oleh')->placeholder('Belum ditinjau'),
                    TextEntry::make('reviewed_at')->label('Ditinjau Pada')->dateTime('d M Y, H:i')->placeholder('Belum ditinjau'),
                    TextEntry::make('review_note')->label('Catatan Peninjau')->placeholder('Tidak ada catatan')->columnSpanFull(),
                ]),
        ]);
    }
}
