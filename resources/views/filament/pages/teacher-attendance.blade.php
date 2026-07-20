<x-filament-panels::page>
    @php($summary = $this->summary())

    <div class="teacher-attendance">
        <section class="teacher-attendance__toolbar" aria-label="Pengaturan presensi">
            <div class="teacher-attendance__filters">
                <label class="teacher-attendance__field">
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

                <label class="teacher-attendance__field">
                    <span>Tanggal Presensi</span>
                    <input type="date" wire:model.live="selectedDate">
                </label>
            </div>

            <div class="teacher-attendance__toolbar-actions">
                <x-filament::button
                    tag="a"
                    :href="$this->attendanceReportUrl()"
                    target="_blank"
                    color="gray"
                    icon="gmdi-picture-as-pdf-o"
                    :disabled="! $selectedClassId"
                >
                    Cetak PDF
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="gmdi-refresh"
                    wire:click="reloadAttendance"
                    wire:loading.attr="disabled"
                >
                    Muat Ulang
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="gmdi-done-all"
                    wire:click="markAllPresent"
                    :disabled="empty($students)"
                >
                    Tandai Semua Hadir
                </x-filament::button>

                <x-filament::button
                    type="button"
                    icon="gmdi-save-o"
                    wire:click="saveAttendance"
                    wire:loading.attr="disabled"
                    :disabled="empty($students)"
                >
                    Simpan Presensi
                </x-filament::button>
            </div>
        </section>

        @if ($pendingSubmissions)
            <section class="teacher-attendance__submissions" aria-labelledby="parent-submissions-title">
                <header class="teacher-attendance__submissions-header">
                    <div>
                        <h2 id="parent-submissions-title">Informasi dari Orang Tua</h2>
                        <p>Tinjau sakit atau izin sebelum melengkapi presensi kelas.</p>
                    </div>
                    <span>{{ count($pendingSubmissions) }} menunggu</span>
                </header>

                <div class="teacher-attendance__submission-list">
                    @foreach ($pendingSubmissions as $submission)
                        <article class="teacher-attendance__submission" wire:key="parent-submission-{{ $submission['id'] }}">
                            <div class="teacher-attendance__submission-icon teacher-attendance__submission-icon--{{ $submission['type'] }}">
                                <x-filament::icon :icon="$submission['type'] === 'sick' ? 'gmdi-medical-information-o' : 'gmdi-description-o'" />
                            </div>
                            <div class="teacher-attendance__submission-body">
                                <div class="teacher-attendance__submission-heading">
                                    <div>
                                        <h3>{{ $submission['student_name'] }}</h3>
                                        <p>{{ $submission['parent_name'] }} - {{ $submission['date_label'] }}</p>
                                    </div>
                                    <span>{{ $submission['type_label'] }}</span>
                                </div>
                                <p class="teacher-attendance__submission-description">{{ $submission['description'] }}</p>
                            </div>
                            <div class="teacher-attendance__submission-actions">
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="success"
                                    icon="gmdi-check-circle-o"
                                    wire:click="approveSubmission({{ $submission['id'] }})"
                                    wire:loading.attr="disabled"
                                >
                                    Terima
                                </x-filament::button>
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="danger"
                                    icon="gmdi-cancel-o"
                                    wire:click="rejectSubmission({{ $submission['id'] }})"
                                    wire:confirm="Tolak informasi dari orang tua ini?"
                                    wire:loading.attr="disabled"
                                >
                                    Tolak
                                </x-filament::button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="teacher-attendance__summary" aria-label="Ringkasan status presensi">
            @foreach ([
                ['key' => 'present', 'label' => 'Hadir', 'icon' => 'gmdi-check-circle-o'],
                ['key' => 'sick', 'label' => 'Sakit', 'icon' => 'gmdi-medical-information-o'],
                ['key' => 'permission', 'label' => 'Izin', 'icon' => 'gmdi-description-o'],
                ['key' => 'absent', 'label' => 'Alpa', 'icon' => 'gmdi-cancel-o'],
                ['key' => 'unrecorded', 'label' => 'Belum Diisi', 'icon' => 'gmdi-pending-actions-o'],
            ] as $item)
                <div class="teacher-attendance__metric teacher-attendance__metric--{{ $item['key'] }}">
                    <x-filament::icon :icon="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $summary[$item['key']] }}</strong>
                </div>
            @endforeach
        </section>

        <section class="teacher-attendance__roster" aria-labelledby="attendance-roster-title">
            <header class="teacher-attendance__roster-header">
                <div>
                    <h2 id="attendance-roster-title">Daftar Siswa</h2>
                    <p>{{ $this->formattedDate() }} - Data Loket dan laporan orang tua ditampilkan otomatis.</p>
                </div>

                <span>{{ count($students) }} siswa</span>
            </header>

            @if (empty($classes))
                <div class="teacher-attendance__empty">
                    <x-filament::icon icon="gmdi-class-o" />
                    <h3>Belum ada kelas yang diampu</h3>
                    <p>Hubungi admin untuk menetapkan kelas kepada akun guru ini.</p>
                </div>
            @elseif (empty($students))
                <div class="teacher-attendance__empty">
                    <x-filament::icon icon="gmdi-groups-o" />
                    <h3>Belum ada siswa aktif</h3>
                    <p>Siswa yang ditempatkan admin ke kelas ini akan muncul otomatis.</p>
                </div>
            @else
                <div class="teacher-attendance__column-headings" aria-hidden="true">
                    <span>Siswa</span>
                    <span>Status Kehadiran</span>
                    <span>Catatan</span>
                </div>

                <div class="teacher-attendance__student-list">
                    @foreach ($students as $student)
                        <article class="teacher-attendance__student" wire:key="attendance-student-{{ $student['id'] }}">
                            <div class="teacher-attendance__identity">
                                <span class="teacher-attendance__avatar" aria-hidden="true">{{ $student['initials'] }}</span>
                                <div>
                                    <h3>{{ $student['name'] }}</h3>
                                    <p>{{ $student['nis'] }}</p>
                                    <div class="teacher-attendance__source-row">
                                        <span class="teacher-attendance__source teacher-attendance__source--{{ $student['source'] ?? 'empty' }}">
                                            {{ $student['source_label'] }}
                                        </span>

                                        @if ($student['is_late'])
                                            <span class="teacher-attendance__late">
                                                <x-filament::icon icon="gmdi-schedule-o" />
                                                Terlambat
                                            </span>
                                        @endif
                                    </div>
                                    @if ($student['source_detail'])
                                        <p class="teacher-attendance__source-detail">{{ $student['source_detail'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <fieldset class="teacher-attendance__status-group">
                                <legend class="sr-only">Status kehadiran {{ $student['name'] }}</legend>
                                @foreach ([
                                    'present' => ['label' => 'Hadir', 'short' => 'H', 'icon' => 'gmdi-check-circle-o'],
                                    'sick' => ['label' => 'Sakit', 'short' => 'S', 'icon' => 'gmdi-medical-information-o'],
                                    'permission' => ['label' => 'Izin', 'short' => 'I', 'icon' => 'gmdi-description-o'],
                                    'absent' => ['label' => 'Alpa', 'short' => 'A', 'icon' => 'gmdi-cancel-o'],
                                ] as $value => $option)
                                    <button
                                        type="button"
                                        class="teacher-attendance__status teacher-attendance__status--{{ $value }} {{ ($attendance[$student['id']] ?? null) === $value ? 'is-selected' : '' }}"
                                        wire:click="$set('attendance.{{ $student['id'] }}', '{{ $value }}')"
                                        aria-pressed="{{ ($attendance[$student['id']] ?? null) === $value ? 'true' : 'false' }}"
                                        title="{{ $option['label'] }}"
                                    >
                                        <x-filament::icon :icon="$option['icon']" />
                                        <span>{{ $option['label'] }}</span>
                                        <strong>{{ $option['short'] }}</strong>
                                    </button>
                                @endforeach
                            </fieldset>

                            <label class="teacher-attendance__note">
                                <span class="sr-only">Catatan untuk {{ $student['name'] }}</span>
                                <input
                                    type="text"
                                    wire:model.blur="notes.{{ $student['id'] }}"
                                    maxlength="1000"
                                    placeholder="Catatan opsional"
                                >
                            </label>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
