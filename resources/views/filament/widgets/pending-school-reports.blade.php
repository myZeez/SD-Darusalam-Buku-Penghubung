@php
    use App\Filament\Pages\DailySchoolActivities;
@endphp

<x-filament-widgets::widget class="fi-wi-pending-school-reports">
    <section class="pending-school-reports" aria-labelledby="pending-school-reports-heading">
        <header class="pending-school-reports__header">
            <div class="pending-school-reports__heading">
                <span class="pending-school-reports__icon" aria-hidden="true">
                    <x-filament::icon icon="gmdi-assignment-late-o" />
                </span>
                <div>
                    <p>Laporan sekolah hari ini</p>
                    <h2 id="pending-school-reports-heading">{{ $pendingStudents->count() }} siswa belum dicatat</h2>
                    <span>{{ $reportedCount }} dari {{ $totalCount }} siswa aktif sudah memiliki laporan.</span>
                </div>
            </div>

            <x-filament::button tag="a" :href="$reportIndexUrl" color="gray" icon="gmdi-list-alt-o" size="sm">
                Lihat Laporan
            </x-filament::button>
        </header>

        @if ($pendingStudents->isNotEmpty())
            <ul class="pending-school-reports__list" aria-label="Siswa yang belum memiliki laporan sekolah hari ini">
                @foreach ($pendingStudents as $student)
                    <li>
                        <span class="pending-school-reports__avatar" aria-hidden="true">
                            {{ \Illuminate\Support\Str::of($student->name)->explode(' ')->filter()->take(2)->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') ?: 'S' }}
                        </span>
                        <div>
                            <strong>{{ $student->name }}</strong>
                            <span>{{ $student->class?->name ?? 'Kelas belum ditentukan' }}</span>
                        </div>
                        <x-filament::button
                            tag="a"
                            :href="DailySchoolActivities::getUrl(['student_id' => $student->id])"
                            icon="gmdi-add-task-o"
                            size="sm"
                        >
                            Isi Laporan
                        </x-filament::button>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="pending-school-reports__empty">
                <x-filament::icon icon="gmdi-task-alt-o" aria-hidden="true" />
                <div>
                    <strong>Semua laporan hari ini sudah lengkap.</strong>
                    <span>Terima kasih, data perkembangan siswa siap ditinjau bersama orang tua.</span>
                </div>
            </div>
        @endif
    </section>
</x-filament-widgets::widget>
