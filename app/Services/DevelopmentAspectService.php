<?php

namespace App\Services;

use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DevelopmentAspectService
{
    /**
     * @var array<string, array{label: string, description: string, icon: string, keywords: array<int, string>}>
     */
    private const ASPECTS = [
        'spiritual' => [
            'label' => 'Spiritual dan Keagamaan',
            'description' => 'Ibadah, doa, dan pembiasaan nilai baik.',
            'icon' => 'gmdi-fact-check-o',
            'keywords' => ['ibadah', 'agama', 'doa', 'berdoa', 'salat', 'sholat', 'mengaji', 'religius'],
        ],
        'cognitive' => [
            'label' => 'Kognitif dan Akademik',
            'description' => 'Belajar, literasi, tugas, dan pemahaman materi.',
            'icon' => 'gmdi-assignment-o',
            'keywords' => ['belajar', 'pembelajaran', 'akademik', 'kognitif', 'literasi', 'tugas', 'pr'],
        ],
        'social' => [
            'label' => 'Sosial Emosional dan Karakter',
            'description' => 'Interaksi, disiplin, tanggung jawab, dan kerja sama.',
            'icon' => 'gmdi-groups-o',
            'keywords' => ['sosial', 'emosional', 'karakter', 'disiplin', 'tanggung jawab', 'kerja sama', 'kerjasama', 'santun'],
        ],
        'physical' => [
            'label' => 'Fisik Motorik dan Kemandirian',
            'description' => 'Kesehatan, motorik, kebersihan, serta rutinitas mandiri.',
            'icon' => 'gmdi-sports-soccer-o',
            'keywords' => ['fisik', 'motorik', 'kemandirian', 'mandiri', 'kebersihan', 'kesehatan', 'tidur', 'makan'],
        ],
    ];

    /**
     * @param  Collection<int, SchoolActivity|HomeActivity>  $schoolActivities
     * @param  Collection<int, SchoolActivity|HomeActivity>  $homeActivities
     * @return array<int, array{key: string, label: string, description: string, icon: string, observations: int}>
     */
    public function summarize(Collection $schoolActivities, Collection $homeActivities): array
    {
        $observations = array_fill_keys(array_keys(self::ASPECTS), 0);

        $schoolActivities
            ->concat($homeActivities)
            ->each(function (SchoolActivity|HomeActivity $activity) use (&$observations): void {
                $activityText = $this->activityText($activity);

                foreach (self::ASPECTS as $key => $aspect) {
                    if (collect($aspect['keywords'])->contains(
                        fn (string $keyword): bool => str_contains($activityText, $keyword),
                    )) {
                        $observations[$key]++;
                    }
                }
            });

        return collect(self::ASPECTS)
            ->map(fn (array $aspect, string $key): array => [
                'key' => $key,
                'label' => $aspect['label'],
                'description' => $aspect['description'],
                'icon' => $aspect['icon'],
                'observations' => $observations[$key],
            ])
            ->values()
            ->all();
    }

    private function activityText(SchoolActivity|HomeActivity $activity): string
    {
        return Str::lower(
            collect($activity->resolvedActivityGroups())
                ->flatMap(function (array $group): array {
                    return [
                        $group['category'],
                        ...collect($group['items'])
                            ->flatMap(fn (array $item): array => [$item['label'], $item['text']])
                            ->all(),
                    ];
                })
                ->filter(fn (mixed $value): bool => filled($value))
                ->join(' '),
        );
    }
}
