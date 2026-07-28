<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardWelcomeIllustration extends Widget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.dashboard-welcome-illustration';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'guru', 'orang_tua']) ?? false;
    }

    /**
     * @return array<string, string>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return [
                'eyebrow' => 'Beranda admin',
                'heading' => 'Pantau kesiswaan dengan lebih dekat',
                'description' => 'Lihat gambaran kehadiran dan aktivitas siswa untuk membantu sekolah mengambil langkah yang tepat.',
                'image' => asset('images/dashboard/school-field.png'),
                'alt' => 'Guru dan siswa melakukan aktivitas di lapangan sekolah',
            ];
        }

        if ($user?->hasRole('guru')) {
            return [
                'eyebrow' => 'Beranda wali kelas',
                'heading' => 'Mari dampingi siswa hari ini',
                'description' => 'Catat kehadiran dan aktivitas siswa agar perkembangan mereka selalu terpantau dengan baik.',
                'image' => asset('images/dashboard/teacher-students.png'),
                'alt' => 'Wali kelas bersama siswa sekolah dasar',
            ];
        }

        return [
            'eyebrow' => 'Beranda orang tua',
            'heading' => 'Tumbuh bersama, setiap hari',
            'description' => 'Ikuti kabar sekolah dan aktivitas anak untuk mendukung perkembangan mereka dari rumah.',
            'image' => asset('images/dashboard/parent-child.png'),
            'alt' => 'Orang tua mendampingi anak sekolah dasar',
        ];
    }
}
