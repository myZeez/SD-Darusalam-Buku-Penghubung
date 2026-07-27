<?php

namespace App\Filament\Resources\HomeActivities\Schemas;

use App\Filament\Forms\ActivityGroupsField;
use App\Models\SchoolClass;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
                        : 'Pilih kelas dan tanggal, lalu susun aktivitas rumah yang perlu dilakukan seluruh siswa.')
                    ->icon(Heroicon::OutlinedHome)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas Tujuan')
                            ->options(fn (): array => self::availableClasses()
                                ->withCount('students')
                                ->orderBy('grade_level')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (SchoolClass $class): array => [
                                    $class->id => "{$class->name} · {$class->students_count} siswa",
                                ])
                                ->all())
                            ->visible(fn (string $operation): bool => $operation === 'create' && ! self::isParent())
                            ->required(fn (string $operation): bool => $operation === 'create' && ! self::isParent())
                            ->dehydrated(fn (string $operation): bool => $operation === 'create' && ! self::isParent())
                            ->searchable()
                            ->preload(),
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
                            ->visible(fn (string $operation): bool => $operation !== 'create')
                            ->required(fn (string $operation): bool => $operation !== 'create')
                            ->disabled(fn (): bool => self::isParent())
                            ->dehydrated(fn (string $operation): bool => $operation !== 'create')
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
                        : 'Daftar mengikuti Buku Penghubung dan diterapkan otomatis ke seluruh siswa di kelas.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->schema([
                        ActivityGroupsField::make(
                            defaults: self::defaultActivityGroups(),
                            checklistItemsOnly: true,
                            parentChecklistOnly: true,
                        )
                            ->visible(fn (): bool => self::isParent()),
                        Placeholder::make('fixed_activity_information')
                            ->label('Daftar aktivitas tetap')
                            ->content('Terdiri dari Berakhlak, Berprestasi, Berjiwa Sosial, dan Peduli Lingkungan. Salat Tahajud otomatis ditambahkan untuk kelas 4–6.')
                            ->visible(fn (): bool => ! self::isParent()),
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

    private static function isParent(): bool
    {
        return auth()->user()?->hasRole('orang_tua') ?? false;
    }

    private static function availableClasses()
    {
        $user = auth()->user();

        return $user?->hasRole('guru')
            ? $user->managedClasses()
            : ($user?->accessibleClasses() ?? SchoolClass::query()->whereRaw('1 = 0'));
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultActivityGroups(): array
    {
        return \App\Models\HomeActivity::defaultActivityGroupsForGrade(null);
    }
}
