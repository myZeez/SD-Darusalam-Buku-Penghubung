<?php

namespace App\Filament\Resources\TeachingAssignments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TeachingAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Penugasan')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('academicPeriod.label')->label('Periode Akademik'),
                    TextEntry::make('teacher.user.name')->label('Guru Pengajar'),
                    TextEntry::make('teacher.nip')->label('NIP')->placeholder('Belum tersedia'),
                    TextEntry::make('schoolClass.name')->label('Kelas'),
                    TextEntry::make('subject.name')->label('Mata Pelajaran'),
                    TextEntry::make('subject.code')->label('Kode')->badge(),
                    IconEntry::make('is_active')->label('Penugasan Aktif')->boolean(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('Tidak ada catatan')->columnSpanFull(),
                ]),
        ]);
    }
}
