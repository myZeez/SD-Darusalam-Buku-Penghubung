<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Mata Pelajaran')
                ->description('Kode digunakan sebagai identitas singkat pada penugasan dan jadwal pelajaran.')
                ->icon(Heroicon::OutlinedBookOpen)
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->placeholder('MTK')
                        ->maxLength(30)
                        ->unique(ignoreRecord: true)
                        ->required(),
                    TextInput::make('name')
                        ->label('Nama Mata Pelajaran')
                        ->placeholder('Matematika')
                        ->maxLength(255)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Mata Pelajaran Aktif')
                        ->default(true)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
