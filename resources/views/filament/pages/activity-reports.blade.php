<x-filament-panels::page>
    @php
        $report = $this->reportData();
        $attendanceLabels = [
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
        ];
    @endphp

    <div class="activity-report-page">
        {{ $this->form }}

        <section class="activity-report-page__download" aria-labelledby="activity-report-download-title">
            <x-filament::icon icon="gmdi-picture-as-pdf-o" />
            <div>
                <h2 id="activity-report-download-title">Unduh Laporan PDF</h2>
                <p>PDF memuat presensi, aktivitas sekolah–rumah, empat aspek perkembangan, serta kolom tanda tangan guru dan orang tua.</p>
            </div>
            <x-filament::button
                tag="a"
                :href="$this->reportUrl()"
                target="_blank"
                icon="gmdi-download-o"
            >
                Unduh PDF
            </x-filament::button>
        </section>

        <section class="student-report-summary" aria-label="Ringkasan laporan">
            @foreach ([
                ['label' => 'Siswa', 'value' => $report['summary']['students'], 'icon' => 'gmdi-groups-o'],
                ['label' => 'Data Presensi', 'value' => $report['summary']['attendance'], 'icon' => 'gmdi-fact-check-o'],
                ['label' => 'Tepat Waktu', 'value' => $report['summary']['on_time'], 'icon' => 'gmdi-schedule-o'],
                ['label' => 'Terlambat', 'value' => $report['summary']['late'], 'icon' => 'gmdi-running-with-errors-o'],
                ['label' => 'Laporan Sekolah', 'value' => $report['summary']['school_activities'], 'icon' => 'gmdi-assignment-o'],
                ['label' => 'Laporan Rumah', 'value' => $report['summary']['home_activities'], 'icon' => 'gmdi-home-work-o'],
            ] as $metric)
                <div class="student-report-summary__metric">
                    <x-filament::icon :icon="$metric['icon']" />
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ $metric['value'] }}</strong>
                </div>
            @endforeach
        </section>

        <div class="student-report-list">
            @forelse ($report['reports'] as $studentReport)
                @php
                    $student = $studentReport['student'];
                    $parentName = $student->parent?->user?->name
                        ?? $student->parent?->father_name
                        ?? $student->parent?->mother_name
                        ?? 'Belum ditentukan';
                    $teacherName = $student->class?->teacher?->user?->name ?? 'Belum ditentukan';
                @endphp

                <article class="student-report">
                    <header class="student-report__header">
                        <div>
                            <span>{{ $student->nis }}</span>
                            <h2>{{ $student->name }}</h2>
                            <p>{{ $student->class?->name ?? 'Belum ada kelas' }} - Guru: {{ $teacherName }} - Orang tua: {{ $parentName }}</p>
                        </div>
                        <span>{{ $studentReport['summary']['attendance'] }} hari tercatat</span>
                    </header>

                    <section class="student-report__section">
                        <div class="student-report__section-heading">
                            <x-filament::icon icon="gmdi-assessment-o" />
                            <div>
                                <h3>Rekap Buku Penghubung</h3>
                                <p>Persentase berdasarkan aktivitas tetap dan skor 5 untuk setiap checklist yang dilakukan.</p>
                            </div>
                        </div>
                        <div class="student-report__score-groups">
                            @foreach ([
                                ['label' => 'Aktivitas Sekolah', 'scores' => $studentReport['summary']['school_activity_scores']],
                                ['label' => 'Aktivitas Rumah', 'scores' => $studentReport['summary']['home_activity_scores']],
                            ] as $scoreGroup)
                                <section>
                                    <h4>{{ $scoreGroup['label'] }}</h4>
                                    @foreach ($scoreGroup['scores'] as $score)
                                        <div class="student-report__score">
                                            <div>
                                                <strong>{{ $score['category'] }}</strong>
                                                <span>{{ $score['rating'] }}</span>
                                                <em>{{ $score['percentage'] }}%</em>
                                            </div>
                                            <i><b style="width: {{ $score['percentage'] }}%"></b></i>
                                            <small>Skor {{ $score['score'] }}/{{ $score['maximum_score'] }}</small>
                                        </div>
                                    @endforeach
                                </section>
                            @endforeach
                        </div>
                    </section>

                    <section class="student-report__section">
                        <div class="student-report__section-heading">
                            <x-filament::icon icon="gmdi-groups-o" />
                            <div>
                                <h3>Empat Aspek Perkembangan</h3>
                                <p>Diringkas dari catatan aktivitas sekolah dan rumah pada periode ini.</p>
                            </div>
                        </div>
                        <div class="student-report__aspects">
                            @foreach ($studentReport['summary']['development_aspects'] as $aspect)
                                <div class="student-report__aspect">
                                    <x-filament::icon :icon="$aspect['icon']" />
                                    <div>
                                        <strong>{{ $aspect['label'] }}</strong>
                                        <span>{{ $aspect['description'] }}</span>
                                    </div>
                                    <em>{{ $aspect['observations'] ? $aspect['observations'].' catatan' : 'Belum ada catatan' }}</em>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="student-report__section">
                        <div class="student-report__section-heading">
                            <x-filament::icon icon="gmdi-fact-check-o" />
                            <div>
                                <h3>Presensi dan Kedatangan</h3>
                                <p>Status hadir serta ketepatan waktu siswa.</p>
                            </div>
                        </div>
                        <div class="student-report__table-wrap">
                            <table class="student-report__table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Presensi</th>
                                        <th>Waktu Datang</th>
                                        <th>Ketepatan</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentReport['attendance_records'] as $attendance)
                                        <tr>
                                            <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                                            <td>{{ $attendanceLabels[$attendance->status] ?? $attendance->status }}</td>
                                            <td>{{ $attendance->arrival?->arrival_time ? substr($attendance->arrival->arrival_time, 0, 5) : '-' }}</td>
                                            <td>
                                                @if ($attendance->is_late)
                                                    Terlambat
                                                @elseif ($attendance->arrival)
                                                    Tepat waktu
                                                @else
                                                    Tidak tercatat
                                                @endif
                                            </td>
                                            <td>{{ $attendance->notes ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5">Belum ada presensi pada periode ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="student-report__section">
                        <div class="student-report__section-heading">
                            <x-filament::icon icon="gmdi-assignment-o" />
                            <div>
                                <h3>Aktivitas Sekolah</h3>
                                <p>{{ $studentReport['summary']['school_activities'] }} laporan dari guru.</p>
                            </div>
                        </div>
                        <div class="student-report__activities">
                            @forelse ($studentReport['school_activities'] as $activity)
                                <div class="student-report__activity">
                                    <div class="student-report__activity-meta">
                                        <strong>{{ $activity->activity_date->format('d/m/Y') }}</strong>
                                        <span>{{ $attendanceLabels[$activity->attendance] ?? $activity->attendance }}</span>
                                    </div>
                                    <x-activity-groups :groups="$activity->resolvedActivityGroups()" compact />
                                    @if ($activity->note)
                                        <p class="student-report__note"><strong>Catatan guru:</strong> {{ $activity->note }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="student-report__empty">Belum ada aktivitas sekolah pada periode ini.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="student-report__section">
                        <div class="student-report__section-heading">
                            <x-filament::icon icon="gmdi-home-work-o" />
                            <div>
                                <h3>Aktivitas Rumah</h3>
                                <p>{{ $studentReport['summary']['home_activities'] }} laporan dari orang tua.</p>
                            </div>
                        </div>
                        <div class="student-report__activities">
                            @forelse ($studentReport['home_activities'] as $activity)
                                <div class="student-report__activity">
                                    <div class="student-report__activity-meta">
                                        <strong>{{ $activity->activity_date->format('d/m/Y') }}</strong>
                                        <span>Orang tua</span>
                                    </div>
                                    <x-activity-groups :groups="$activity->resolvedActivityGroups()" compact />
                                    @if ($activity->note)
                                        <p class="student-report__note"><strong>Catatan orang tua:</strong> {{ $activity->note }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="student-report__empty">Belum ada aktivitas rumah pada periode ini.</p>
                            @endforelse
                        </div>
                    </section>
                </article>
            @empty
                <div class="student-report-list__empty">
                    <x-filament::icon icon="gmdi-search-off-o" />
                    <h2>Tidak ada siswa yang dapat ditampilkan</h2>
                    <p>Ubah filter siswa atau periksa kembali hubungan kelas dan akun.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
