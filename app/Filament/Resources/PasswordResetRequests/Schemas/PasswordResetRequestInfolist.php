<?php

namespace App\Filament\Resources\PasswordResetRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PasswordResetRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Akun Pemohon')
                ->icon('gmdi-person-o')
                ->columns(['md' => 2])
                ->schema([
                    TextEntry::make('user.name')->label('Nama Akun'),
                    TextEntry::make('user.email')->label('Email Login'),
                    TextEntry::make('student.name')->label('Siswa Terkait')->placeholder('Belum terhubung'),
                    TextEntry::make('student.class.name')->label('Kelas')->placeholder('Belum ada kelas'),
                    TextEntry::make('teacher.name')->label('Wali Kelas')->placeholder('Belum tersedia'),
                    TextEntry::make('request_note')->label('Keterangan Pemohon')->placeholder('Tidak ada')->columnSpanFull(),
                ]),
            Section::make('Pemrosesan')
                ->icon('gmdi-admin-panel-settings-o')
                ->columns(['md' => 2])
                ->schema([
                    TextEntry::make('default_password')
                        ->label('Password Default')
                        ->state(fn (): string => (string) config('school.default_login_password'))
                        ->copyable()
                        ->columnSpanFull(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending' => 'Menunggu',
                            'processed' => 'Selesai',
                            'rejected' => 'Ditolak',
                            default => $state,
                        }),
                    TextEntry::make('processor.name')->label('Diproses Oleh')->placeholder('Belum diproses'),
                    TextEntry::make('processed_at')->label('Waktu Proses')->dateTime('d M Y, H:i')->placeholder('Belum diproses'),
                    TextEntry::make('admin_note')->label('Catatan Admin')->placeholder('Tidak ada')->columnSpanFull(),
                ]),
        ]);
    }
}
