<x-filament-panels::page>
    @php
        $template = $this->template();
        $selectedStudent = $this->selectedStudent();
    @endphp

    <div class="daily-activity">
        <section class="daily-activity__toolbar" aria-label="Pengaturan aktivitas sekolah">
            <div class="daily-activity__filters">
                <label class="daily-activity__field">
                    <span>Kelas</span>
                    <select wire:model.live="selectedClassId" @disabled(count($classes) <= 1)>
                        @forelse ($classes as $class)
                            <option value="{{ $class['id'] }}">
                                {{ $class['name'] }} - {{ $class['students_count'] }} siswa
                            </option>
                        @empty
                            <option value="">Belum memiliki kelas</option>
                        @endforelse
                    </select>
                </label>

                <label class="daily-activity__field">
                    <span>Hari dan Tanggal</span>
                    <input type="date" wire:model.live="selectedDate">
                </label>
            </div>

            <div class="daily-activity__toolbar-copy">
                <span>{{ $this->formattedDate() }}</span>
                <strong>{{ count($students) }} siswa aktif</strong>
            </div>

            <div class="daily-activity__export-actions" aria-label="Pilihan export PDF">
                <x-filament::button
                    tag="a"
                    :href="$this->dailyReportUrl($selectedStudentId)"
                    target="_blank"
                    color="gray"
                    icon="gmdi-person-o"
                    :disabled="! $selectedStudentId"
                >
                    Export Siswa Ini
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$this->dailyReportUrl()"
                    target="_blank"
                    icon="gmdi-picture-as-pdf-o"
                    :disabled="empty($students)"
                >
                    Export Semua Siswa
                </x-filament::button>
            </div>

            <x-filament::button
                type="button"
                icon="gmdi-save-o"
                wire:click="saveChecklist"
                wire:loading.attr="disabled"
                :disabled="empty($students) || ! $this->isSchoolDay()"
            >
                Simpan Checklist
            </x-filament::button>
        </section>

        @if (! $this->isSchoolDay())
            <section class="daily-activity__notice daily-activity__notice--warning">
                <x-filament::icon icon="gmdi-event-busy-o" />
                <div>
                    <strong>Aktivitas sekolah tidak dijadwalkan pada hari ini.</strong>
                    <span>Pilih hari aktif sesuai Pengaturan Sekolah. Aktivitas rumah tetap tersedia setiap hari.</span>
                </div>
            </section>
        @endif

        <section class="daily-activity__student-overview" aria-label="Progres aktivitas tiap siswa">
            <header>
                <div>
                    <h2>Progres Siswa Hari Ini</h2>
                    <p>Progres dihitung per siswa, bukan gabungan satu kelas.</p>
                </div>
                <span>{{ count($students) }} siswa aktif</span>
            </header>

            <div>
                @forelse ($students as $student)
                    @php($studentProgress = $this->studentCompletion($student['id']))
                    @php($progress = $studentProgress['total'] ? round(($studentProgress['checked'] / $studentProgress['total']) * 100) : 0)
                    <article>
                        <span class="daily-activity__avatar">{{ $student['initials'] }}</span>
                        <div class="daily-activity__student-overview-name">
                            <strong>{{ $student['name'] }}</strong>
                            <small>{{ $student['nis'] }} · {{ $student['attendance_label'] }}</small>
                        </div>
                        <div class="daily-activity__student-overview-progress">
                            <span>{{ $studentProgress['checked'] }}/{{ $studentProgress['total'] }} aktivitas · {{ $progress }}%</span>
                            <i><b style="width: {{ $progress }}%"></b></i>
                        </div>
                        <span @class(['daily-activity__student-overview-status', 'is-saved' => $student['is_saved']])>
                            {{ $student['is_saved'] ? 'Sudah diisi' : 'Belum diisi' }}
                        </span>
                        <x-filament::button
                            type="button"
                            color="gray"
                            size="sm"
                            icon="gmdi-edit-note-o"
                            wire:click="editStudent({{ $student['id'] }})"
                        >
                            Isi siswa
                        </x-filament::button>
                    </article>
                @empty
                    <div class="daily-activity__empty">
                        <x-filament::icon icon="gmdi-group-off-o" />
                        <h3>Belum ada siswa aktif</h3>
                        <p>Tambahkan siswa aktif ke kelas ini sebelum mengisi aktivitas harian.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="daily-activity__workspace">
            <header class="daily-activity__workspace-header">
                <div>
                    <h2>Isi Aktivitas Sekolah</h2>
                    <p>Pilih cara mengisi data yang paling sesuai untuk kelas hari ini.</p>
                </div>

                <div class="daily-activity__mode" role="group" aria-label="Cara mengisi checklist">
                    <button
                        type="button"
                        @class(['is-active' => $mode === 'activity'])
                        wire:click="setMode('activity')"
                    >
                        <x-filament::icon icon="gmdi-checklist-o" />
                        Isi Semua Siswa
                    </button>
                    <button
                        type="button"
                        @class(['is-active' => $mode === 'student'])
                        wire:click="setMode('student')"
                    >
                        <x-filament::icon icon="gmdi-person-o" />
                        Isi Per Siswa
                    </button>
                </div>
            </header>

            @if (empty($students))
                <div class="daily-activity__empty">
                    <x-filament::icon icon="gmdi-group-off-o" />
                    <h3>Belum ada siswa aktif</h3>
                    <p>Tambahkan siswa aktif ke kelas ini sebelum mengisi aktivitas harian.</p>
                </div>
            @elseif ($mode === 'activity')
                <div class="daily-activity__categories">
                    @foreach ($template as $group)
                        <section class="daily-activity__category">
                            <header>
                                <div>
                                    <h3>{{ $group['category'] }}</h3>
                                    <span>{{ count($group['items']) }} aktivitas tetap</span>
                                </div>
                            </header>

                            <div class="daily-activity__activity-list">
                                @foreach ($group['items'] as $item)
                                    @php($completion = $this->activityCompletion($item['key']))
                                    <article class="daily-activity__activity" wire:key="school-activity-{{ $item['key'] }}">
                                        <header>
                                            <div class="daily-activity__activity-title">
                                                <span @class(['is-complete' => $completion['checked'] === $completion['total'] && $completion['total'] > 0])>
                                                    <x-filament::icon :icon="$completion['checked'] === $completion['total'] && $completion['total'] > 0 ? 'gmdi-check-circle-o' : 'gmdi-radio-button-unchecked-o'" />
                                                </span>
                                                <div>
                                                    <h4>{{ $item['label'] }}</h4>
                                                    @if ($item['key'] === 'on-time-arrival')
                                                        <p>Otomatis mengikuti data Presensi Kelas.</p>
                                                    @else
                                                        <p>{{ $completion['checked'] }} dari {{ $completion['total'] }} siswa dipilih.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($item['key'] !== 'on-time-arrival')
                                                <div class="daily-activity__bulk-actions">
                                                    <button type="button" wire:click="markActivityForAll('{{ $item['key'] }}', true)">
                                                        Pilih semua
                                                    </button>
                                                    <button type="button" wire:click="markActivityForAll('{{ $item['key'] }}', false)">
                                                        Kosongkan
                                                    </button>
                                                </div>
                                            @endif
                                        </header>

                                        <div class="daily-activity__student-checks">
                                            @foreach ($students as $student)
                                                <label @class([
                                                    'is-checked' => $checks[$student['id']][$item['key']] ?? false,
                                                    'is-readonly' => $item['key'] === 'on-time-arrival',
                                                ])>
                                                    <input
                                                        type="checkbox"
                                                        wire:model.live="checks.{{ $student['id'] }}.{{ $item['key'] }}"
                                                        @disabled($item['key'] === 'on-time-arrival')
                                                    >
                                                    <span>{{ $student['initials'] }}</span>
                                                    <strong>{{ $student['name'] }}</strong>
                                                </label>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div class="daily-activity__student-mode">
                    <aside class="daily-activity__student-picker">
                        <label class="daily-activity__field">
                            <span>Pilih Siswa</span>
                            <select wire:model.live="selectedStudentId">
                                @foreach ($students as $student)
                                    @php($studentProgress = $this->studentCompletion($student['id']))
                                    <option value="{{ $student['id'] }}">
                                        {{ $student['name'] }} - {{ $studentProgress['checked'] }}/{{ $studentProgress['total'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @if ($selectedStudent)
                            @php($studentProgress = $this->studentCompletion($selectedStudent['id']))
                            <div class="daily-activity__student-summary">
                                <span>{{ $selectedStudent['initials'] }}</span>
                                <div>
                                    <strong>{{ $selectedStudent['name'] }}</strong>
                                    <small>{{ $selectedStudent['nis'] }} · {{ $selectedStudent['attendance_label'] }}</small>
                                </div>
                                <em>{{ $studentProgress['checked'] }}/{{ $studentProgress['total'] }}</em>
                            </div>

                            <div class="daily-activity__bulk-actions daily-activity__bulk-actions--wide">
                                <button type="button" wire:click="markStudentActivities(true)">Centang semua</button>
                                <button type="button" wire:click="markStudentActivities(false)">Kosongkan</button>
                            </div>
                        @endif
                    </aside>

                    @if ($selectedStudent)
                        <div class="daily-activity__student-form">
                            @foreach ($template as $group)
                                <fieldset>
                                    <legend>{{ $group['category'] }}</legend>
                                    <div>
                                        @foreach ($group['items'] as $item)
                                            <label @class(['is-checked' => $checks[$selectedStudent['id']][$item['key']] ?? false])>
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="checks.{{ $selectedStudent['id'] }}.{{ $item['key'] }}"
                                                    @disabled($item['key'] === 'on-time-arrival')
                                                >
                                                <span>
                                                    <x-filament::icon :icon="($checks[$selectedStudent['id']][$item['key']] ?? false) ? 'gmdi-check-circle-o' : 'gmdi-radio-button-unchecked-o'" />
                                                </span>
                                                <strong>{{ $item['label'] }}</strong>
                                                @if ($item['key'] === 'on-time-arrival')
                                                    <small>Dari presensi</small>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach

                            <label class="daily-activity__note">
                                <span>Informasi / Tugas Guru</span>
                                <textarea
                                    rows="4"
                                    wire:model="notes.{{ $selectedStudent['id'] }}"
                                    placeholder="Tambahkan informasi khusus untuk siswa ini bila diperlukan..."
                                ></textarea>
                            </label>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
