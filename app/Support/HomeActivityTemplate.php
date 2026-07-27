<?php

namespace App\Support;

class HomeActivityTemplate
{
    /** @return array<int, array{category: string, items: array<int, array{label: string, type: string, checked: bool}>}> */
    public static function forGrade(?int $gradeLevel): array
    {
        $akhlak = [
            ...($gradeLevel !== null && $gradeLevel >= 4
                ? ['Melaksanakan salat Tahajud']
                : []),
            'Melaksanakan salat Subuh',
            'Melaksanakan salat Duha',
            'Melaksanakan salat Zuhur',
            'Melaksanakan salat Asar',
            'Melaksanakan salat Magrib',
            'Melaksanakan salat Isya',
            'Membiasakan adab makan dan minum',
            'Berwudu dan berdoa sebelum tidur',
            'Berpakaian rapi dan sopan',
        ];

        return [
            [
                'category' => 'Berakhlak',
                'items' => self::checklistItems($akhlak),
            ],
            [
                'category' => 'Berprestasi',
                'items' => self::checklistItems([
                    'Bangun pagi dan merapikan tempat tidur',
                    'Belajar dengan tekun',
                    "Mengulang hafalan surah, hadis, doa, dan bacaan UMMI/Al-Qur'an",
                    'Rutin berolahraga',
                    'Mengonsumsi makanan sehat dan bergizi',
                    'Tidur lebih awal',
                ]),
            ],
            [
                'category' => 'Berjiwa Sosial',
                'items' => self::checklistItems([
                    'Hormat dan patuh kepada orang tua',
                    'Berbicara dengan sopan kepada semua orang',
                    'Berinfak',
                    'Membantu orang tua atau orang lain',
                ]),
            ],
            [
                'category' => 'Peduli Lingkungan',
                'items' => self::checklistItems([
                    'Membuang sampah pada tempatnya',
                    'Menjaga kebersihan dan kerapian rumah',
                ]),
            ],
        ];
    }

    /** @param array<int, string> $labels
     *  @return array<int, array{label: string, type: string, checked: bool}>
     */
    private static function checklistItems(array $labels): array
    {
        return array_map(
            fn (string $label): array => [
                'label' => $label,
                'type' => 'checklist',
                'checked' => false,
            ],
            $labels,
        );
    }
}
