<?php

namespace App\Filament\Resources\SchoolActivities\Schemas;

use App\Filament\Forms\ActivityGroupsField;
use App\Models\SchoolActivity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class SchoolActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Laporan')
                    ->description('Tentukan siswa, pencatat, tanggal, dan kehadiran.')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('student_id')
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();

                                    return $user
                                        ? $query->whereIn('students.id', $user->accessibleStudents()->select('students.id'))
                                        : $query->whereRaw('1 = 0');
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Siswa'),
                        Select::make('teacher_id')
                            ->relationship('teacher', 'nip')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->user?->name ?? $record->nip ?? 'Guru')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->label('Guru'),
                        DatePicker::make('activity_date')
                            ->label('Tanggal Aktivitas')
                            ->default(now())
                            ->unique(
                                table: SchoolActivity::class,
                                column: 'activity_date',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('student_id', $get('student_id')),
                            )
                            ->validationMessages([
                                'unique' => SchoolActivity::DUPLICATE_DAILY_REPORT_MESSAGE,
                            ])
                            ->helperText('Satu siswa hanya memiliki satu laporan sekolah per tanggal.')
                            ->required(),
                        Select::make('attendance')
                            ->label('Kehadiran')
                            ->options([
                                'present' => 'Hadir',
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'absent' => 'Alpa',
                            ])
                            ->required()
                            ->default('present'),
                    ]),
                Section::make('Isi Aktivitas')
                    ->description('Sesuaikan kategori dan jenis isian dengan kegiatan siswa pada hari ini.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->schema([
                        ActivityGroupsField::make(self::defaultActivityGroups()),
                    ]),
                Section::make('Catatan dan Dokumentasi')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 2,
                    ])
                    ->schema([
                        Textarea::make('note')
                            ->label('Catatan Guru')
                            ->rows(6),
                        FileUpload::make('photo')
                            ->label('Foto Aktivitas')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->directory('school-activities'),
                    ]),
            ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultActivityGroups(): array
    {
        return [
            [
                'category' => 'Kegiatan Ibadah',
                'items' => [
                    ['label' => 'Berdoa', 'type' => 'checklist', 'checked' => false],
                    ['label' => 'Salat', 'type' => 'checklist', 'checked' => false],
                    ['label' => 'Mengaji', 'type' => 'checklist', 'checked' => false],
                ],
            ],
            [
                'category' => 'Kegiatan Pembelajaran',
                'items' => [
                    ['label' => 'Catatan Pembelajaran', 'type' => 'text', 'text' => ''],
                ],
            ],
            [
                'category' => 'Aspek Perkembangan',
                'items' => [
                    ['label' => 'Catatan Perkembangan', 'type' => 'text', 'text' => ''],
                ],
            ],
        ];
    }
}
