<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Presensi Siswa')
                ->description('Kelas mengikuti penempatan siswa. Status dapat dikoreksi oleh admin atau wali kelas yang berwenang.')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->columns(['md' => 2])
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->relationship(
                            name: 'student',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->whereIn('students.id', auth()->user()->accessibleStudents()->select('students.id'))
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
                        ->required(),
                    DatePicker::make('attendance_date')
                        ->label('Tanggal Presensi')
                        ->default(today())
                        ->required(),
                    ToggleButtons::make('status')
                        ->label('Status Kehadiran')
                        ->options([
                            'present' => 'Hadir',
                            'sick' => 'Sakit',
                            'permission' => 'Izin',
                            'absent' => 'Alpa',
                        ])
                        ->icons([
                            'present' => Heroicon::OutlinedCheckCircle,
                            'sick' => Heroicon::OutlinedHeart,
                            'permission' => Heroicon::OutlinedDocumentText,
                            'absent' => Heroicon::OutlinedXCircle,
                        ])
                        ->colors([
                            'present' => 'success',
                            'sick' => 'warning',
                            'permission' => 'info',
                            'absent' => 'danger',
                        ])
                        ->grouped()
                        ->required()
                        ->default('present')
                        ->columnSpanFull(),
                    Toggle::make('is_late')
                        ->label('Datang Terlambat')
                        ->helperText('Status ini biasanya terisi otomatis dari catatan Piket.'),
                    Textarea::make('notes')
                        ->label('Catatan Presensi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Hidden::make('source')->default('manual'),
                ]),
        ]);
    }
}
