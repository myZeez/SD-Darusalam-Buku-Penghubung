<?php

namespace App\Services;

use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Models\SchoolSetting;
use App\Support\ActivityGroups;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ActivityScoreService
{
    public const POINTS_PER_ACTIVITY = 5;

    /**
     * @param  Collection<int, SchoolActivity|HomeActivity>  $records
     * @param  array<int, array<string, mixed>>  $template
     * @return array<int, array{category: string, checked: int, possible: int, score: int, maximum_score: int, percentage: int, rating: string, rating_label: string, color: string}>
     */
    public function summarize(
        Collection $records,
        array $template,
        string $from,
        string $to,
        string $scope,
        int $studentCount = 1,
    ): array {
        $eligibleDays = $this->eligibleDayCount($from, $to, $scope);
        $studentCount = max(1, $studentCount);
        $checkedByCategory = [];

        foreach ($records as $record) {
            foreach ($record->resolvedActivityGroups() as $group) {
                $checkedByCategory[$group['category']] = ($checkedByCategory[$group['category']] ?? 0)
                    + collect($group['items'])->where('checked', true)->count();
            }
        }

        return collect(ActivityGroups::normalize($template))
            ->map(function (array $group) use ($checkedByCategory, $eligibleDays, $studentCount): array {
                $checked = (int) ($checkedByCategory[$group['category']] ?? 0);
                $possible = count($group['items']) * $eligibleDays * $studentCount;
                $score = $checked * self::POINTS_PER_ACTIVITY;
                $maximumScore = $possible * self::POINTS_PER_ACTIVITY;
                $percentage = $maximumScore > 0
                    ? (int) round(($score / $maximumScore) * 100)
                    : 0;

                return [
                    'category' => $group['category'],
                    'checked' => $checked,
                    'possible' => $possible,
                    'score' => $score,
                    'maximum_score' => $maximumScore,
                    'percentage' => min(100, $percentage),
                    'rating' => self::rating($percentage),
                    'rating_label' => self::ratingLabel($percentage),
                    'color' => self::color($percentage),
                ];
            })
            ->values()
            ->all();
    }

    public static function rating(int $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'A',
            $percentage >= 70 => 'B',
            $percentage >= 55 => 'C',
            default => 'D',
        };
    }

    public static function ratingLabel(int $percentage): string
    {
        return match (self::rating($percentage)) {
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            default => 'Perlu Bimbingan',
        };
    }

    public static function color(int $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'success',
            $percentage >= 70 => 'primary',
            $percentage >= 55 => 'warning',
            default => 'danger',
        };
    }

    private function eligibleDayCount(string $from, string $to, string $scope): int
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();
        $schoolDays = collect(SchoolSetting::current()->school_days ?? [])
            ->map(fn (mixed $day): string => strtolower((string) $day))
            ->all();
        $count = 0;

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dayName = strtolower($date->format('l'));

            if ($scope === 'home' || in_array($dayName, $schoolDays, true)) {
                $count++;
            }
        }

        return max(1, $count);
    }
}
