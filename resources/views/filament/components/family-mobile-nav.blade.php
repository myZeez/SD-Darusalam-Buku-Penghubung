@php
    use App\Filament\Resources\ActivityComments\ActivityCommentResource;
    use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
    use App\Filament\Resources\HomeActivities\HomeActivityResource;
    use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
    use App\Filament\Resources\ParentProfiles\ParentProfileResource;
    use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
    use App\Filament\Resources\Schedules\ScheduleResource;
    use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
    use App\Filament\Resources\Students\StudentResource;
    use App\Filament\Pages\ActivityReports;

    $user = auth()->user();
    $isFamilyUser = $user?->hasAnyRole(['orang_tua', 'siswa']) ?? false;

    $sharedItems = [
        [
            'label' => 'Beranda',
            'icon' => 'gmdi-dashboard-o',
            'url' => route('filament.admin.pages.dashboard'),
            'active' => request()->routeIs('filament.admin.pages.dashboard'),
        ],
    ];

    $studentItems = [
        ...$sharedItems,
        [
            'label' => 'Profil',
            'icon' => 'gmdi-badge-o',
            'url' => StudentResource::getNavigationUrl(),
            'active' => request()->routeIs('filament.admin.resources.students.*'),
        ],
        [
            'label' => 'Ekskul',
            'icon' => 'gmdi-sports-soccer-o',
            'url' => ExtracurricularResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.extracurriculars.*'),
        ],
    ];

    $parentItems = [
        ...$sharedItems,
        [
            'label' => 'Profil',
            'icon' => 'gmdi-family-restroom-o',
            'url' => ParentProfileResource::getNavigationUrl(),
            'active' => request()->routeIs('filament.admin.resources.parent-profiles.*'),
        ],
        [
            'label' => 'Anak',
            'icon' => 'gmdi-badge-o',
            'url' => StudentResource::getNavigationUrl(),
            'active' => request()->routeIs('filament.admin.resources.students.*'),
        ],
        [
            'label' => 'Presensi',
            'icon' => 'gmdi-fact-check-o',
            'url' => AttendanceRecordResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.attendance-records.*'),
        ],
        [
            'label' => 'Sekolah',
            'icon' => 'gmdi-assignment-o',
            'url' => SchoolActivityResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.school-activities.*'),
        ],
        [
            'label' => 'Rumah',
            'icon' => 'gmdi-home-work-o',
            'url' => HomeActivityResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.home-activities.*'),
        ],
        [
            'label' => 'Diskusi',
            'icon' => 'gmdi-forum-o',
            'url' => ActivityCommentResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.activity-comments.*'),
        ],
        [
            'label' => 'Izin',
            'icon' => 'gmdi-approval-o',
            'url' => ParentSubmissionResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.parent-submissions.*'),
        ],
        [
            'label' => 'Agenda',
            'icon' => 'gmdi-calendar-month-o',
            'url' => ScheduleResource::getUrl(),
            'active' => request()->routeIs('filament.admin.resources.schedules.*'),
        ],
        [
            'label' => 'Laporan',
            'icon' => 'gmdi-picture-as-pdf-o',
            'url' => ActivityReports::getUrl(),
            'active' => request()->routeIs('filament.admin.pages.activity-reports'),
        ],
    ];

    $items = match (true) {
        $user?->hasRole('siswa') => $studentItems,
        $user?->hasRole('orang_tua') => $parentItems,
        default => [],
    };
@endphp

@if ($isFamilyUser)
    <nav
        @class([
            'family-mobile-nav',
            'family-mobile-nav--scrollable' => count($items) > 6,
        ])
        aria-label="Navigasi utama ponsel"
        style="--family-nav-columns: {{ count($items) }}"
    >
        @foreach ($items as $item)
            <a
                href="{{ $item['url'] }}"
                @class([
                    'family-mobile-nav__item',
                    'family-mobile-nav__item--active' => $item['active'],
                ])
                @if ($item['active']) aria-current="page" @endif
            >
                <span class="family-mobile-nav__icon-wrap">
                    <x-filament::icon :icon="$item['icon']" class="family-mobile-nav__icon" />

                    @if (($item['badge'] ?? 0) > 0)
                        <span
                            class="family-mobile-nav__badge"
                            aria-label="{{ min($item['badge'], 99) }} notifikasi belum dibaca"
                        >
                            {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                        </span>
                    @endif
                </span>
                <span class="family-mobile-nav__label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
