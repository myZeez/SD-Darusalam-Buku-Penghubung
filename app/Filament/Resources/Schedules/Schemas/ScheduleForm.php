<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Agenda')
                    ->description('Informasi utama kegiatan yang akan ditampilkan pada jadwal.')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('class_id')
                            ->relationship(
                                name: 'schoolClass',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();

                                    return $user?->isAdmin()
                                        ? $query
                                        : $query->whereIn('classes.id', $user?->managedClasses()->select('classes.id'));
                                },
                            )
                            ->label('Target Kelas')
                            ->searchable()
                            ->preload()
                            ->required(fn (): bool => auth()->user()?->hasRole('guru') ?? false)
                            ->helperText(fn (): string => auth()->user()?->isAdmin()
                                ? 'Kosongkan untuk membuat agenda umum bagi seluruh sekolah.'
                                : 'Guru hanya dapat membuat agenda untuk kelas binaannya.'),
                        TextInput::make('title')
                            ->label('Judul Kegiatan')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                        DatePicker::make('activity_date')
                            ->label('Tanggal Kegiatan')
                            ->default(now())
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Waktu dan Persiapan')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TimePicker::make('start_time')
                            ->label('Waktu Mulai')
                            ->seconds(false),
                        TimePicker::make('end_time')
                            ->label('Waktu Selesai')
                            ->seconds(false)
                            ->afterOrEqual('start_time'),
                        Textarea::make('equipment')
                            ->label('Perlengkapan')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
