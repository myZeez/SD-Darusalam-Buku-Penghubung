<?php

namespace App\Filament\Resources\Extracurriculars\RelationManagers;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Models\ExtracurricularSession;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Sesi, Materi, dan Absensi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('session_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Kegiatan')
                    ->description(fn (ExtracurricularSession $record): string => filled($record->start_time)
                        ? substr($record->start_time, 0, 5).' - '.substr($record->end_time ?? $record->start_time, 0, 5)
                        : 'Waktu belum dicatat')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('material')
                    ->label('Materi')
                    ->limit(70)
                    ->wrap()
                    ->placeholder('Belum ada materi'),
                TextColumn::make('coach_attendance_status')
                    ->label('Kehadiran Pelatih')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'sick', 'permission' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => self::attendanceLabel($state)),
                TextColumn::make('attendances_count')
                    ->label('Absensi Siswa')
                    ->counts('attendances')
                    ->suffix(' siswa'),
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->square()
                    ->defaultImageUrl(null),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Sesi Kegiatan')
                    ->visible(fn (): bool => $this->canManage())
                    ->mutateDataUsing(function (array $data): array {
                        $data['teacher_id'] = $this->getOwnerRecord()->teacher_id;

                        return $data;
                    })
                    ->form($this->formSchema(includeAttendance: false)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Isi Absensi dan Materi')
                    ->icon('gmdi-fact-check-o')
                    ->visible(fn (): bool => $this->canManage())
                    ->form($this->formSchema(includeAttendance: true)),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManage()),
            ])
            ->defaultSort('session_date', 'desc')
            ->emptyStateHeading('Belum ada sesi kegiatan')
            ->emptyStateDescription('Buat sesi untuk mencatat materi, foto, dan kehadiran siswa serta pelatih.');
    }

    /** @return array<int, mixed> */
    private function formSchema(bool $includeAttendance): array
    {
        $schema = [
            DatePicker::make('session_date')
                ->label('Tanggal Kegiatan')
                ->default(today())
                ->required(),
            TextInput::make('title')
                ->label('Judul Kegiatan')
                ->required(),
            TimePicker::make('start_time')
                ->label('Jam Mulai')
                ->seconds(false),
            TimePicker::make('end_time')
                ->label('Jam Selesai')
                ->seconds(false)
                ->after('start_time'),
            Select::make('coach_attendance_status')
                ->label('Kehadiran Pelatih')
                ->options(self::attendanceOptions())
                ->default('present')
                ->required(),
            Textarea::make('coach_notes')
                ->label('Catatan Kehadiran Pelatih')
                ->rows(2),
            Textarea::make('material')
                ->label('Materi Kegiatan')
                ->rows(4)
                ->columnSpanFull(),
            Textarea::make('notes')
                ->label('Catatan Kegiatan')
                ->rows(3)
                ->columnSpanFull(),
            FileUpload::make('photo')
                ->label('Foto Kegiatan')
                ->image()
                ->imageEditor()
                ->maxSize(5120)
                ->directory('extracurriculars')
                ->columnSpanFull(),
        ];

        if ($includeAttendance) {
            $schema[] = Repeater::make('attendances')
                ->label('Absensi Siswa')
                ->relationship('attendances')
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->relationship('student', 'name')
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    Select::make('status')
                        ->label('Kehadiran')
                        ->options(self::attendanceOptions())
                        ->required(),
                    TextInput::make('notes')
                        ->label('Catatan'),
                ])
                ->columns(['md' => 3])
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull();
        }

        return $schema;
    }

    /** @return array<string, string> */
    private static function attendanceOptions(): array
    {
        return [
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
        ];
    }

    private static function attendanceLabel(string $status): string
    {
        return self::attendanceOptions()[$status] ?? $status;
    }

    private function canManage(): bool
    {
        return ExtracurricularResource::canEdit($this->getOwnerRecord());
    }
}
