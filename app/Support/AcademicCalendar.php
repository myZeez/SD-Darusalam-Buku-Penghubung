<?php

namespace App\Support;

class AcademicCalendar
{
    public const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
    ];

    public const SEMESTERS = [
        'odd' => 'Ganjil',
        'even' => 'Genap',
    ];

    public const PERIOD_TYPES = [
        'lesson' => 'Pelajaran',
        'break' => 'Istirahat',
        'ceremony' => 'Apel / Upacara',
        'other' => 'Kegiatan Lain',
    ];

    public static function dayLabel(?string $day): string
    {
        return self::DAYS[$day] ?? (string) $day;
    }

    public static function semesterLabel(?string $semester): string
    {
        return self::SEMESTERS[$semester] ?? (string) $semester;
    }
}
