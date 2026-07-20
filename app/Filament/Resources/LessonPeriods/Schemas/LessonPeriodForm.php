<?php

namespace App\Filament\Resources\LessonPeriods\Schemas;

use App\Models\AcademicPeriod;
use App\Models\LessonPeriod;
use App\Support\AcademicCalendar;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class LessonPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Susun Waktu Sekolah')
                ->description('Buat seluruh urutan waktu dalam sekali simpan. Jam mulai baris baru otomatis mengikuti jam selesai sebelumnya.')
                ->icon(Heroicon::OutlinedClock)
                ->columnSpanFull()
                ->visibleOn('create')
                ->schema([
                    self::academicPeriodSelect(controlsTimeline: true),
                    Repeater::make('periods')
                        ->label('Urutan Kegiatan')
                        ->helperText('Bagian ini hanya mengatur waktu. Mata pelajaran seperti Matematika atau IPS dipilih nanti pada menu Jadwal Pelajaran.')
                        ->schema([
                            Select::make('type')
                                ->label('Kegiatan')
                                ->options(AcademicCalendar::PERIOD_TYPES)
                                ->native(false)
                                ->default('lesson')
                                ->live()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    $startTime = self::shortTime($get('start_time'));

                                    if ($startTime === null) {
                                        return;
                                    }

                                    $set(
                                        'end_time',
                                        self::addMinutes($startTime, self::suggestedDuration($state)),
                                        shouldCallUpdatedHooks: true,
                                    );
                                })
                                ->required(),
                            TimePicker::make('start_time')
                                ->label('Jam Mulai')
                                ->seconds(false)
                                ->live(onBlur: true)
                                ->required(),
                            TimePicker::make('end_time')
                                ->label('Jam Selesai')
                                ->seconds(false)
                                ->after('start_time')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (TimePicker $component, ?string $old, ?string $state) => self::shiftFollowingPeriods(
                                    $component,
                                    $old,
                                    $state,
                                ))
                                ->required(),
                        ])
                        ->columns(['md' => 3])
                        ->default(fn (): array => self::defaultTimelineForPeriod(
                            AcademicPeriod::query()->where('is_active', true)->value('id'),
                        ))
                        ->afterStateUpdated(fn (Repeater $component, ?array $old): Repeater => self::syncTimeline($component, $old))
                        ->itemLabel(fn (array $state, int $index): string => sprintf(
                            '%d. %s%s',
                            $index + 1,
                            AcademicCalendar::PERIOD_TYPES[$state['type'] ?? 'lesson'] ?? 'Kegiatan',
                            filled($state['start_time'] ?? null) && filled($state['end_time'] ?? null)
                                ? sprintf('  •  %s–%s', self::shortTime($state['start_time']), self::shortTime($state['end_time']))
                                : '',
                        ))
                        ->addActionLabel('Tambah Waktu Berikutnya')
                        ->addActionAlignment(Alignment::Start)
                        ->reorderable(false)
                        ->minItems(1)
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make('Ubah Rentang Waktu')
                ->description('Nama dan urutan dapat disesuaikan tanpa mengubah periode lainnya.')
                ->icon(Heroicon::OutlinedClock)
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->visibleOn('edit')
                ->schema([
                    self::academicPeriodSelect(),
                    Select::make('type')
                        ->label('Kegiatan')
                        ->options(AcademicCalendar::PERIOD_TYPES)
                        ->native(false)
                        ->required(),
                    TextInput::make('name')
                        ->label('Nama Periode')
                        ->placeholder('Jam Pelajaran 1')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('sequence')
                        ->label('Urutan')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required(),
                    TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->seconds(false)
                        ->required(),
                    TimePicker::make('end_time')
                        ->label('Jam Selesai')
                        ->seconds(false)
                        ->after('start_time')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Periode Aktif')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function academicPeriodSelect(bool $controlsTimeline = false): Select
    {
        $select = Select::make('academic_period_id')
            ->relationship('academicPeriod', 'academic_year')
            ->getOptionLabelFromRecordUsing(fn (AcademicPeriod $record): string => $record->label)
            ->default(fn (): ?int => AcademicPeriod::query()->where('is_active', true)->value('id'))
            ->searchable()
            ->preload()
            ->required()
            ->label('Periode Akademik')
            ->columnSpanFull();

        if ($controlsTimeline) {
            $select
                ->live()
                ->afterStateUpdated(fn ($state, Set $set) => $set(
                    'periods',
                    self::defaultTimelineForPeriod(filled($state) ? (int) $state : null),
                ));
        }

        return $select;
    }

    private static function defaultTimelineForPeriod(?int $academicPeriodId): array
    {
        $lastPeriod = filled($academicPeriodId)
            ? LessonPeriod::query()
                ->where('academic_period_id', $academicPeriodId)
                ->orderByDesc('end_time')
                ->first()
            : null;

        if ($lastPeriod) {
            $startTime = self::shortTime($lastPeriod->end_time);

            return [[
                'type' => 'lesson',
                'start_time' => $startTime,
                'end_time' => self::addMinutes($startTime, 60),
            ]];
        }

        return [
            [
                'type' => 'lesson',
                'start_time' => '07:00',
                'end_time' => '08:00',
            ],
            [
                'type' => 'break',
                'start_time' => '08:00',
                'end_time' => '08:30',
            ],
            [
                'type' => 'lesson',
                'start_time' => '08:30',
                'end_time' => '09:30',
            ],
        ];
    }

    private static function syncTimeline(Repeater $component, ?array $old): Repeater
    {
        $items = $component->getRawState() ?? [];
        $oldItems = array_values($old ?? []);
        $changed = false;
        $previousEnd = null;

        foreach ($items as $index => &$item) {
            $type = $item['type'] ?? 'lesson';
            $startTime = self::shortTime($item['start_time'] ?? null);
            $endTime = self::shortTime($item['end_time'] ?? null);

            if ($index === array_key_first($items)) {
                if ($startTime === null) {
                    $startTime = '07:00';
                    $item['start_time'] = $startTime;
                    $changed = true;
                }
            } elseif ($previousEnd !== null) {
                $numericIndex = array_search($index, array_keys($items), true);
                $oldPreviousEnd = self::shortTime($oldItems[$numericIndex - 1]['end_time'] ?? null);
                $shouldFollowPrevious = $startTime === null || $startTime === $oldPreviousEnd;

                if ($shouldFollowPrevious && $startTime !== $previousEnd) {
                    $duration = self::durationInMinutes($startTime, $endTime)
                        ?? self::suggestedDuration($type);
                    $startTime = $previousEnd;
                    $endTime = self::addMinutes($startTime, $duration);
                    $item['start_time'] = $startTime;
                    $item['end_time'] = $endTime;
                    $changed = true;
                }
            }

            if ($startTime !== null && $endTime === null) {
                $endTime = self::addMinutes($startTime, self::suggestedDuration($type));
                $item['end_time'] = $endTime;
                $changed = true;
            }

            $previousEnd = $endTime;
        }

        unset($item);

        if ($changed) {
            $component->rawState($items);
        }

        return $component;
    }

    private static function shiftFollowingPeriods(TimePicker $component, ?string $old, ?string $state): void
    {
        $repeater = $component->getContainer()->getParentComponent();

        if (! $repeater instanceof Repeater) {
            return;
        }

        $oldPreviousEnd = self::shortTime($old);
        $previousEnd = self::shortTime($state);

        if ($oldPreviousEnd === null || $previousEnd === null || $oldPreviousEnd === $previousEnd) {
            return;
        }

        $items = $repeater->getRawState() ?? [];
        $keys = array_keys($items);
        $statePathParts = explode('.', (string) $component->getStatePath());
        $currentKey = $statePathParts[count($statePathParts) - 2] ?? null;
        $currentIndex = array_search($currentKey, $keys, true);

        if ($currentIndex === false) {
            return;
        }

        foreach (array_slice($keys, $currentIndex + 1) as $key) {
            $startTime = self::shortTime($items[$key]['start_time'] ?? null);
            $endTime = self::shortTime($items[$key]['end_time'] ?? null);

            if ($startTime !== $oldPreviousEnd) {
                break;
            }

            $duration = self::durationInMinutes($startTime, $endTime)
                ?? self::suggestedDuration($items[$key]['type'] ?? null);
            $oldPreviousEnd = $endTime;
            $items[$key]['start_time'] = $previousEnd;
            $items[$key]['end_time'] = self::addMinutes($previousEnd, $duration);
            $previousEnd = $items[$key]['end_time'];
        }

        $repeater->rawState($items);
    }

    private static function suggestedDuration(?string $type): int
    {
        return $type === 'lesson' ? 60 : 30;
    }

    private static function durationInMinutes(?string $startTime, ?string $endTime): ?int
    {
        if ($startTime === null || $endTime === null) {
            return null;
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $startTime));
        [$endHour, $endMinute] = array_map('intval', explode(':', $endTime));
        $duration = (($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute);

        return $duration > 0 ? $duration : null;
    }

    private static function addMinutes(string $time, int $minutes): string
    {
        return (new \DateTimeImmutable($time))->modify("+{$minutes} minutes")->format('H:i');
    }

    private static function shortTime(mixed $time): ?string
    {
        return filled($time) ? substr((string) $time, 0, 5) : null;
    }
}
