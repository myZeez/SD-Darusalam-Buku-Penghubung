<?php

namespace App\Filament\Resources\EarlyDepartures\Schemas;

use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class EarlyDepartureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Catat Pulang Cepat')
                ->description('Gunakan untuk siswa yang pulang sebelum jam sekolah selesai. Sertakan alasan atau catatan.')
                ->icon(Heroicon::OutlinedArrowLeftStartOnRectangle)
                ->columns(['md' => 2])
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->relationship(
                            name: 'student',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereIn('students.id', auth()->user()->managedStudents()->select('students.id'))
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
                        ->required()
                        ->columnSpanFull(),
                    DatePicker::make('departure_date')
                        ->label('Tanggal Pulang')
                        ->default(today())
                        ->required(),
                    TimePicker::make('departure_time')
                        ->label('Jam Pulang')
                        ->default(now()->format('H:i'))
                        ->seconds(false)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->placeholder('Contoh: kontrol kesehatan atau keperluan keluarga.')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
