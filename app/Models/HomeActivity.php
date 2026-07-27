<?php

namespace App\Models;

use App\Support\ActivityGroups;
use App\Support\HomeActivityTemplate;
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
        'submitted_at',
        'submitted_by',
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
            'submitted_at' => 'datetime',
        ];
    }

    /** @return array<int, array{category: string, items: array<int, array<string, mixed>>}> */
    public function resolvedActivityGroups(): array
    {
        $groups = ActivityGroups::normalize($this->activity_groups);

        if (filled($groups)) {
            return $groups;
        }

        return self::defaultActivityGroupsForGrade($this->student?->class?->grade_level);
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultActivityGroupsForGrade(?int $gradeLevel): array
    {
        return HomeActivityTemplate::forGrade($gradeLevel);
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

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
