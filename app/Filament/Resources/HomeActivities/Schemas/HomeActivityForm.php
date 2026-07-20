<?php

namespace App\Filament\Resources\HomeActivities\Schemas;

use App\Filament\Forms\ActivityGroupsField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class HomeActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Aktivitas')
                    ->description(fn (): string => self::singleLinkedStudentId()
                        ? 'Siswa dan orang tua diambil otomatis dari hubungan keluarga.'
                        : 'Pilih anak yang akan dilaporkan. Orang tua diambil otomatis dari hubungan keluarga siswa.')
                    ->icon(Heroicon::OutlinedHome)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('student_id')
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();

                                    return $user
                                        ? $query
                                            ->whereIn('students.id', $user->accessibleStudents()->select('students.id'))
                                            ->whereNotNull('parent_id')
                                        : $query->whereRaw('1 = 0');
                                },
                            )
                            ->default(fn (): ?int => self::singleLinkedStudentId())
                            ->disabled(fn (string $operation): bool => $operation === 'create' && filled(self::singleLinkedStudentId()))
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Siswa'),
                        DatePicker::make('activity_date')
                            ->label('Tanggal Aktivitas')
                            ->default(now())
                            ->required(),
                    ]),
                Section::make('Isi Aktivitas')
                    ->description('Sesuaikan kategori dan jenis isian dengan kegiatan anak di rumah.')
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
                            ->label('Catatan Orang Tua')
                            ->rows(6),
                        FileUpload::make('photo')
                            ->label('Foto Aktivitas')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->directory('home-activities'),
                    ]),
            ]);
    }

    private static function singleLinkedStudentId(): ?int
    {
        $user = auth()->user();

        if (! $user?->hasRole('orang_tua')) {
            return null;
        }

        $studentIds = $user->accessibleStudents()
            ->whereNotNull('parent_id')
            ->limit(2)
            ->pluck('students.id');

        return $studentIds->count() === 1 ? (int) $studentIds->first() : null;
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
                'category' => 'Kebiasaan Harian',
                'items' => [
                    ['label' => 'Belajar', 'type' => 'checklist', 'checked' => false],
                    ['label' => 'Mengerjakan PR', 'type' => 'checklist', 'checked' => false],
                    ['label' => 'Tidur Tepat Waktu', 'type' => 'checklist', 'checked' => false],
                    ['label' => 'Makan Teratur', 'type' => 'checklist', 'checked' => false],
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
