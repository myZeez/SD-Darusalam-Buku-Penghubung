<?php

namespace App\Models;

use App\Support\ActivityGroups;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SchoolActivity extends Model
{
    public const DUPLICATE_DAILY_REPORT_MESSAGE = 'Laporan sekolah untuk siswa dan tanggal tersebut sudah ada. Buka laporan yang sudah ada untuk melakukan perubahan.';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'activity_date',
        'attendance',
        'activity_groups',
        'morning_activity',
        'learning_activity',
        'religious_activity',
        'character_building',
        'motoric_activity',
        'break_activity',
        'cleanliness',
        'independence',
        'note',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'activity_groups' => 'array',
        ];
    }

    /** @return array<int, array{category: string, items: array<int, array<string, mixed>>}> */
    public function resolvedActivityGroups(): array
    {
        $groups = ActivityGroups::normalize($this->activity_groups);

        if (filled($groups)) {
            return $groups;
        }

        return ActivityGroups::normalize([
            [
                'category' => 'Pembelajaran dan Karakter',
                'items' => $this->legacyTextItems([
                    'Kegiatan Pagi' => $this->morning_activity,
                    'Kegiatan Pembelajaran' => $this->learning_activity,
                    'Pembentukan Karakter' => $this->character_building,
                    'Kemandirian' => $this->independence,
                ]),
            ],
            [
                'category' => 'Kebiasaan dan Perkembangan',
                'items' => $this->legacyTextItems([
                    'Kegiatan Keagamaan' => $this->religious_activity,
                    'Kegiatan Motorik' => $this->motoric_activity,
                    'Aktivitas Waktu Istirahat' => $this->break_activity,
                    'Kebersihan' => $this->cleanliness,
                ]),
            ],
        ]);
    }

    protected function activityCategorySummary(): Attribute
    {
        return Attribute::get(fn (): string => ActivityGroups::categoryNames($this->resolvedActivityGroups()));
    }

    /** @param array<string, mixed> $items */
    private function legacyTextItems(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $text): bool => filled($text))
            ->map(fn (mixed $text, string $label): array => [
                'label' => $label,
                'type' => 'text',
                'text' => (string) $text,
            ])
            ->values()
            ->all();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function comments()
    {
        return $this->morphMany(ActivityComment::class, 'activity');
    }
}
