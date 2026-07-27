<?php

namespace App\Filament\Resources\ParentSubmissions\Schemas;

use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ParentSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan Orang Tua')
                ->description('Pengajuan selalu terhubung ke orang tua berdasarkan data keluarga siswa.')
                ->icon(Heroicon::OutlinedDocumentText)
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
                            '%s (%s)',
                            $student->name,
                            $student->class?->name ?? 'belum ada kelas',
                        ))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    Placeholder::make('class_context')
                        ->label('Kelas')
                        ->content(fn (Get $get): string => self::student($get)?->class?->name ?? 'Pilih siswa terlebih dahulu'),
                    Placeholder::make('teacher_context')
                        ->label('Guru Kelas')
                        ->content(function (Get $get): string {
                            $class = self::student($get)?->class;
                            $teachers = collect([
                            $class?->teacher?->user?->name,
                        ])->filter()->join(' dan ');

                            return $teachers ?: 'Belum ada guru yang ditetapkan';
                        }),
                    Select::make('type')
                        ->label('Status Kehadiran')
                        ->options([
                            'sick' => 'Sakit',
                            'permission' => 'Izin',
                            'early_leave' => 'Izin Pulang Cepat',
                        ])
                        ->required(),
                    DatePicker::make('start_date')
                        ->label('Mulai Tanggal')
                        ->default(today())
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->afterOrEqual('start_date'),
                    TimePicker::make('early_leave_time')
                        ->label('Rencana Jam Pulang')
                        ->seconds(false)
                        ->visible(fn (Get $get): bool => $get('type') === 'early_leave')
                        ->required(fn (Get $get): bool => $get('type') === 'early_leave')
                        ->dehydratedWhenHidden(false),
                    Textarea::make('description')
                        ->label('Deskripsi Singkat')
                        ->placeholder('Contoh: Demam sejak tadi malam dan perlu beristirahat di rumah.')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    FileUpload::make('attachment')
                        ->label('Lampiran')
                        ->directory('parent-submissions')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->maxSize(5120)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function student(Get $get): ?Student
    {
        $studentId = $get('student_id');

        return $studentId
            ? Student::query()->with('class.teacher.user')->find($studentId)
            : null;
    }
}
