<?php

namespace App\Filament\Resources\StudentArrivals\Schemas;

use App\Models\SchoolSetting;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class StudentArrivalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Catat Keterlambatan')
                ->description('Pilih hanya siswa yang datang terlambat. Catatan ini tidak digunakan untuk presensi hadir.')
                ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->columns(['md' => 2])
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->relationship(
                            name: 'student',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('status', 'active')
                                ->with('class')
                                ->orderBy('name'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Student $student): string => sprintf(
                            '%s - %s (%s)',
                            $student->nis,
                            $student->name,
                            $student->class?->name ?? 'belum ada kelas',
                        ))
                        ->searchable(['nis', 'name'])
                        ->preload()
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    Placeholder::make('class_preview')
                        ->label('Kelas')
                        ->content(fn (Get $get): string => Student::query()
                            ->with('class')
                            ->find($get('student_id'))
                            ?->class?->name ?? 'Pilih siswa terlebih dahulu'),
                    Placeholder::make('deadline_preview')
                        ->label('Batas Tepat Waktu')
                        ->content(function (): string {
                            $setting = SchoolSetting::current();

                            return substr($setting->school_start_time, 0, 5)
                                .($setting->late_tolerance_minutes ? " + {$setting->late_tolerance_minutes} menit toleransi" : '');
                        }),
                    DatePicker::make('arrival_date')
                        ->label('Tanggal Keterlambatan')
                        ->default(today())
                        ->disabled(fn (): bool => auth()->user()?->hasRole('loket') ?? false)
                        ->dehydrated()
                        ->required(),
                    TimePicker::make('arrival_time')
                        ->label('Waktu Tercatat')
                        ->default(fn (): string => now(SchoolSetting::current()->timezone)->format('H:i'))
                        ->seconds(false)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->placeholder('Opsional, misalnya alasan keterlambatan yang disampaikan siswa.')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
