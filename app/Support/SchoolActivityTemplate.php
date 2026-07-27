<?php

namespace App\Support;

class SchoolActivityTemplate
{
    /** @return array<int, array{category: string, items: array<int, array{key: string, label: string, type: string, checked: bool}>}> */
    public static function forGrade(?int $gradeLevel): array
    {
        $akhlak = [
            ['on-time-arrival', 'Hadir di sekolah tepat waktu'],
            ['duha-prayer', 'Melaksanakan salat Duha'],
            ['zuhur-prayer', 'Melaksanakan salat Zuhur'],
            ...($gradeLevel !== null && $gradeLevel >= 4
                ? [['ashar-prayer', 'Melaksanakan salat Ashar']]
                : []),
            ['murajaah', 'Murajaah hafalan surah, hadis, dan doa'],
            ['eating-etiquette', 'Membiasakan adab makan dan minum'],
            ['neat-dress', 'Berpakaian rapi dan sopan'],
        ];

        return [
            [
                'category' => 'Berakhlak',
                'items' => self::checklistItems($akhlak),
            ],
            [
                'category' => 'Berprestasi',
                'items' => self::checklistItems([
                    ['orderly-learning', 'Belajar dengan tertib dan disiplin'],
                    ['on-time-assignment', 'Mengerjakan tugas tepat waktu'],
                    ['library-reading', 'Membaca buku di perpustakaan'],
                    ['daily-language', 'Menggunakan ungkapan sederhana dalam bahasa Inggris, Arab, atau Dayak'],
                    ['exercise', 'Rutin berolahraga'],
                ]),
            ],
            [
                'category' => 'Berjiwa Sosial',
                'items' => self::checklistItems([
                    ['five-s', 'Membiasakan 5S (Senyum, Salam, Sapa, Sopan, Santun)'],
                    ['respect-teacher', 'Hormat dan patuh kepada guru'],
                    ['respect-friend', 'Menghormati dan menghargai teman'],
                    ['polite-speaking', 'Berbicara dengan sopan kepada semua orang'],
                    ['queue', 'Membiasakan antre'],
                    ['charity', 'Berinfak'],
                    ['helping', 'Membantu guru atau teman'],
                ]),
            ],
            [
                'category' => 'Peduli Lingkungan',
                'items' => self::checklistItems([
                    ['dispose-trash', 'Membuang sampah pada tempatnya'],
                    ['tidy-personal-area', 'Menjaga kebersihan dan kerapian meja, laci, dan loker'],
                    ['care-school-environment', 'Menjaga dan merawat lingkungan sekolah'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $items
     * @return array<int, array{key: string, label: string, type: string, checked: bool}>
     */
    private static function checklistItems(array $items): array
    {
        return array_map(
            fn (array $item): array => [
                'key' => $item[0],
                'label' => $item[1],
                'type' => 'checklist',
                'checked' => false,
            ],
            $items,
        );
    }
}
