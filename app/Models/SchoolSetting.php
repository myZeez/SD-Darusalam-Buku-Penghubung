<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_name',
        'npsn',
        'principal_name',
        'address',
        'phone',
        'email',
        'logo',
        'academic_year',
        'school_start_time',
        'late_tolerance_minutes',
        'school_days',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'late_tolerance_minutes' => 'integer',
            'school_days' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'school_name' => 'Sekolah Dasar Islam Darussalam',
            'academic_year' => '2026/2027',
            'school_start_time' => '07:00:00',
            'school_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function arrivalDeadline(CarbonInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $date->format('Y-m-d').' '.$this->school_start_time,
            $this->timezone,
        )->addMinutes($this->late_tolerance_minutes);
    }
}
