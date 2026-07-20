<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ActivityGroupsField
{
    /** @param array<int, array<string, mixed>> $defaults */
    public static function make(array $defaults): Repeater
    {
        return Repeater::make('activity_groups')
            ->label('Kategori Aktivitas')
            ->helperText('Buat kategori, lalu tambahkan aktivitas checklist atau catatan teks di dalamnya.')
            ->schema([
                TextInput::make('category')
                    ->label('Nama Kategori')
                    ->placeholder('Contoh: Kegiatan Ibadah')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Daftar Aktivitas')
                    ->schema([
                        TextInput::make('label')
                            ->label('Nama Aktivitas')
                            ->placeholder('Contoh: Salat atau Catatan Perkembangan')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(['md' => 5]),
                        ToggleButtons::make('type')
                            ->label('Jenis Isian')
                            ->options([
                                'checklist' => 'Checklist',
                                'text' => 'Teks',
                            ])
                            ->icons([
                                'checklist' => Heroicon::OutlinedCheckCircle,
                                'text' => Heroicon::OutlinedPencilSquare,
                            ])
                            ->grouped()
                            ->live()
                            ->required()
                            ->default('checklist')
                            ->columnSpan(['md' => 4]),
                        Toggle::make('checked')
                            ->label('Sudah Dilakukan')
                            ->visible(fn (Get $get): bool => $get('type') === 'checklist')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'checklist')
                            ->default(false)
                            ->columnSpan(['md' => 3]),
                        Textarea::make('text')
                            ->label('Isi Catatan')
                            ->placeholder('Tuliskan perkembangan atau aktivitas siswa...')
                            ->rows(3)
                            ->visible(fn (Get $get): bool => $get('type') === 'text')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'text')
                            ->columnSpanFull(),
                    ])
                    ->columns(['md' => 12])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(30)
                    ->itemLabel(fn (array $state): string => $state['label'] ?? 'Aktivitas Baru')
                    ->addActionLabel('Tambah Aktivitas')
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->reorderableWithDragAndDrop(false)
                    ->columnSpanFull(),
            ])
            ->default(fn (string $operation): array => $operation === 'create' ? $defaults : [])
            ->minItems(1)
            ->maxItems(12)
            ->itemLabel(fn (array $state): string => $state['category'] ?? 'Kategori Baru')
            ->addActionLabel('Tambah Kategori')
            ->cloneable()
            ->reorderableWithButtons()
            ->reorderableWithDragAndDrop(false)
            ->columnSpanFull();
    }
}
