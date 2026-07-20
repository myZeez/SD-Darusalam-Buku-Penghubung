<?php

namespace App\Models;

use App\Support\ActivityGroups;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class HomeActivity extends Model
{
    protected $fillable = [
        'student_id',
        'parent_id',
        'activity_date',
        'activity_groups',
        'worship',
        'study',
        'homework',
        'sleep',
        'meal',
        'note',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'activity_groups' => 'array',
            'worship' => 'boolean',
            'study' => 'boolean',
            'homework' => 'boolean',
            'sleep' => 'boolean',
            'meal' => 'boolean',
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
                'category' => 'Kebiasaan Harian',
                'items' => [
                    ['label' => 'Ibadah', 'type' => 'checklist', 'checked' => $this->worship],
                    ['label' => 'Belajar', 'type' => 'checklist', 'checked' => $this->study],
                    ['label' => 'Mengerjakan PR', 'type' => 'checklist', 'checked' => $this->homework],
                    ['label' => 'Tidur Tepat Waktu', 'type' => 'checklist', 'checked' => $this->sleep],
                    ['label' => 'Makan Teratur', 'type' => 'checklist', 'checked' => $this->meal],
                ],
            ],
        ]);
    }

    protected function activityCategorySummary(): Attribute
    {
        return Attribute::get(fn (): string => ActivityGroups::categoryNames($this->resolvedActivityGroups()));
    }

    protected static function booted(): void
    {
        static::saving(function (HomeActivity $activity): void {
            if (! $activity->student_id) {
                return;
            }

            $activity->parent_id = Student::query()
                ->whereKey($activity->student_id)
                ->value('parent_id');
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function comments()
    {
        return $this->morphMany(ActivityComment::class, 'activity');
    }
}
