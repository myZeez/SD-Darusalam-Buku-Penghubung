<?php

namespace App\Filament\Resources\LessonPeriods\Pages;

use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use App\Models\LessonPeriod;
use App\Support\AcademicCalendar;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLessonPeriod extends CreateRecord
{
    protected static string $resource = LessonPeriodResource::class;

    protected static bool $canCreateAnother = false;

    protected int $savedPeriodCount = 0;

    public function getTitle(): string|Htmlable
    {
        return 'Susun Periode Pelajaran';
    }

    public function getSubheading(): ?string
    {
        return 'Atur alur waktu sekolah dari kegiatan pertama sampai selesai.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $periods = array_values($data['periods'] ?? []);
        $errors = [];

        foreach ($periods as $index => $period) {
            $startTime = substr((string) ($period['start_time'] ?? ''), 0, 5);
            $endTime = substr((string) ($period['end_time'] ?? ''), 0, 5);

            if ($startTime >= $endTime) {
                $errors["periods.{$index}.end_time"] = 'Jam selesai harus setelah jam mulai.';

                continue;
            }

            foreach (array_slice($periods, 0, $index) as $otherPeriod) {
                $otherStart = substr((string) ($otherPeriod['start_time'] ?? ''), 0, 5);
                $otherEnd = substr((string) ($otherPeriod['end_time'] ?? ''), 0, 5);

                if ($startTime < $otherEnd && $endTime > $otherStart) {
                    $errors["periods.{$index}.start_time"] = 'Waktu ini bertabrakan dengan baris sebelumnya.';
                    break;
                }
            }

            $overlapsExistingPeriod = LessonPeriod::query()
                ->where('academic_period_id', $data['academic_period_id'])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->exists();

            if ($overlapsExistingPeriod) {
                $errors["periods.{$index}.start_time"] = 'Waktu ini bertabrakan dengan periode yang sudah tersimpan.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $periods = array_values(Arr::pull($data, 'periods', []));
        $academicPeriodId = (int) $data['academic_period_id'];

        return DB::transaction(function () use ($academicPeriodId, $periods): LessonPeriod {
            $nextSequence = ((int) LessonPeriod::query()
                ->where('academic_period_id', $academicPeriodId)
                ->max('sequence')) + 1;
            $typeCounts = LessonPeriod::query()
                ->where('academic_period_id', $academicPeriodId)
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->map(fn ($count): int => (int) $count)
                ->all();
            $firstPeriod = null;

            foreach ($periods as $index => $periodData) {
                $type = $periodData['type'];
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;

                $period = LessonPeriod::query()->create([
                    'academic_period_id' => $academicPeriodId,
                    'name' => $this->periodName($type, $typeCounts[$type]),
                    'sequence' => $nextSequence + $index,
                    'start_time' => $periodData['start_time'],
                    'end_time' => $periodData['end_time'],
                    'type' => $type,
                    'is_active' => true,
                ]);

                $firstPeriod ??= $period;
                $this->savedPeriodCount++;
            }

            return $firstPeriod;
        });
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Susunan Waktu')
            ->icon('gmdi-check-circle-o');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "{$this->savedPeriodCount} periode waktu berhasil disimpan";
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl();
    }

    private function periodName(string $type, int $number): string
    {
        return match ($type) {
            'lesson' => "Jam Pelajaran {$number}",
            'break' => $number === 1 ? 'Istirahat' : "Istirahat {$number}",
            'ceremony' => $number === 1 ? AcademicCalendar::PERIOD_TYPES[$type] : AcademicCalendar::PERIOD_TYPES[$type]." {$number}",
            default => "Kegiatan Lain {$number}",
        };
    }
}
