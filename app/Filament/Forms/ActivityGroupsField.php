<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ActivityGroupsField
{
    /** @param array<int, array<string, mixed>> $defaults */
    public static function make(
        array $defaults,
        bool $checklistItemsOnly = false,
        bool $parentChecklistOnly = false,
    ): Repeater
    {
        $itemSchema = [
            TextInput::make('label')
                ->label('Nama Aktivitas')
                ->placeholder('Contoh: Salat')
                ->required()
                ->maxLength(150)
                ->disabled($parentChecklistOnly)
                ->dehydrated()
                ->columnSpan(['md' => $checklistItemsOnly ? 9 : 5]),
        ];

        if ($checklistItemsOnly) {
            $itemSchema[] = Hidden::make('type')
                ->default('checklist');
        } else {
            $itemSchema[] = ToggleButtons::make('type')
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
                ->disabled($parentChecklistOnly)
                ->dehydrated()
                ->columnSpan(['md' => 4]);
        }

        $itemSchema[] = $parentChecklistOnly
            ? Toggle::make('checked')
                ->label('Sudah Dilakukan')
                ->default(false)
                ->columnSpan(['md' => 3])
            : Hidden::make('checked')
                ->default(false);

        if (! $checklistItemsOnly) {
            $itemSchema[] = Textarea::make('text')
                ->label($parentChecklistOnly ? 'Keterangan dari Guru' : 'Isi Catatan')
                ->placeholder('Tuliskan perkembangan atau aktivitas siswa...')
                ->rows(3)
                ->visible(fn (Get $get): bool => $get('type') === 'text')
                ->disabled($parentChecklistOnly)
                ->dehydrated()
                ->columnSpanFull();
        }

        return Repeater::make('activity_groups')
            ->label('Kategori Aktivitas')
            ->helperText($parentChecklistOnly
                ? 'Centang aktivitas yang sudah dilakukan. Tambahan informasi dapat ditulis pada catatan orang tua.'
                : ($checklistItemsOnly
                    ? 'Buat kategori, lalu tambahkan aktivitas yang perlu dilakukan siswa.'
                    : 'Buat kategori, lalu tambahkan aktivitas checklist atau catatan teks di dalamnya.'))
            ->schema([
                TextInput::make('category')
                    ->label('Nama Kategori')
                    ->placeholder('Contoh: Kegiatan Ibadah')
                    ->required()
                    ->maxLength(100)
                    ->disabled($parentChecklistOnly)
                    ->dehydrated()
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Daftar Aktivitas')
                    ->schema($itemSchema)
                    ->columns(['md' => 12])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(30)
                    ->itemLabel(fn (array $state): string => $state['label'] ?? 'Aktivitas Baru')
                    ->addActionLabel('Tambah Aktivitas')
                    ->addable(! $parentChecklistOnly)
                    ->deletable(! $parentChecklistOnly)
                    ->cloneable(! $parentChecklistOnly)
                    ->reorderable(! $parentChecklistOnly)
                    ->reorderableWithButtons(! $parentChecklistOnly)
                    ->reorderableWithDragAndDrop(false)
                    ->columnSpanFull(),
            ])
            ->default(fn (string $operation): array => $operation === 'create' ? $defaults : [])
            ->minItems(1)
            ->maxItems(12)
            ->itemLabel(fn (array $state): string => $state['category'] ?? 'Kategori Baru')
            ->addActionLabel('Tambah Kategori')
            ->addable(! $parentChecklistOnly)
            ->deletable(! $parentChecklistOnly)
            ->cloneable(! $parentChecklistOnly)
            ->reorderable(! $parentChecklistOnly)
            ->reorderableWithButtons(! $parentChecklistOnly)
            ->reorderableWithDragAndDrop(false)
            ->columnSpanFull();
    }
}
