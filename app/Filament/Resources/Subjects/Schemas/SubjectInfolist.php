<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SubjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Mata Pelajaran')
                ->icon(Heroicon::OutlinedBookOpen)
                ->columns(['md' => 2])
                ->schema([
                    TextEntry::make('code')->label('Kode')->badge(),
                    TextEntry::make('name')->label('Nama Mata Pelajaran'),
                    IconEntry::make('is_active')->label('Status Aktif')->boolean(),
                    TextEntry::make('assignments_count')
                        ->label('Jumlah Penugasan')
                        ->state(fn ($record): int => $record->teachingAssignments()->count())
                        ->badge(),
                    TextEntry::make('description')->label('Deskripsi')->placeholder('Tidak ada deskripsi')->columnSpanFull(),
                ]),
        ]);
    }
}
