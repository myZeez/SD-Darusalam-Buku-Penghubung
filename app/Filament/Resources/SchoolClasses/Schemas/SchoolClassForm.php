<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Kelas')
                    ->description('Penempatan wali kelas, tingkat, dan kapasitas dikelola oleh admin.')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('teacher_id')
                            ->relationship('teacher', 'nip')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->user?->name ?? $record->nip ?? 'Guru')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manage classes') ?? false))
                            ->label('Wali Kelas'),
                        TextInput::make('name')
                            ->label('Nama Kelas')
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manage classes') ?? false)),
                        Select::make('grade_level')
                            ->label('Tingkat Kelas')
                            ->options(collect(range(1, 6))->mapWithKeys(fn (int $grade): array => [$grade => "Kelas {$grade}"])->all())
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manage classes') ?? false)),
                        TextInput::make('capacity')
                            ->label('Kapasitas Maksimum')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(30)
                            ->required()
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manage classes') ?? false)),
                        TextInput::make('room')
                            ->label('Ruang Kelas')
                            ->placeholder('Contoh: Ruang 1A')
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manage classes') ?? false)),
                    ]),
                Section::make('Catatan Kelas')
                    ->description('Informasi operasional yang dapat diperbarui wali kelas.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('description')
                            ->label('Deskripsi dan Catatan')
                            ->rows(6),
                    ]),
            ]);
    }
}
