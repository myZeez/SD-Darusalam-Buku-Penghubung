<?php

namespace App\Filament\Resources\HomeActivities\Schemas;

use App\Filament\Forms\ActivityGroupsField;
use Filament\Forms\Components\DatePicker;
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
                    ->description(fn (): string => self::isParent()
                        ? 'Siswa dan tanggal aktivitas ditentukan oleh guru.'
                        : 'Pilih siswa dan tanggal, lalu susun aktivitas rumah yang perlu dilakukan.')
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
                                            ->whereIn('students.id', ($user->hasRole('guru')
                                                ? $user->managedStudents()
                                                : $user->accessibleStudents())->select('students.id'))
                                            ->whereNotNull('parent_id')
                                        : $query->whereRaw('1 = 0');
                                },
                            )
                            ->default(fn (): ?int => self::singleLinkedStudentId())
                            ->disabled(fn (string $operation): bool => self::isParent() || ($operation === 'create' && filled(self::singleLinkedStudentId())))
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Siswa'),
                        DatePicker::make('activity_date')
                            ->label('Tanggal Pelaksanaan')
                            ->default(now())
                            ->disabled(fn (): bool => self::isParent())
                            ->dehydrated()
                            ->required(),
                    ]),
                Section::make(fn (): string => self::isParent() ? 'Checklist Aktivitas' : 'Daftar Aktivitas untuk Siswa')
                    ->description(fn (): string => self::isParent()
                        ? 'Centang hanya aktivitas yang telah dilakukan oleh siswa.'
                        : 'Aktivitas ini akan muncul sebagai checklist pada akun orang tua.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->schema([
                        ActivityGroupsField::make(
                            defaults: self::defaultActivityGroups(),
                            checklistItemsOnly: true,
                            parentChecklistOnly: self::isParent(),
                        ),
                    ]),
                Section::make('Catatan Orang Tua')
                    ->description('Gunakan bagian ini untuk tambahan informasi yang tidak ada pada daftar aktivitas.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('note')
                            ->label('Catatan Tambahan')
                            ->rows(6)
                            ->disabled(fn (): bool => ! self::isParent())
                            ->dehydrated(),
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

    private static function isParent(): bool
    {
        return auth()->user()?->hasRole('orang_tua') ?? false;
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
        ];
    }
}
