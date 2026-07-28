<x-filament-panels::page>
    @php
        $template = $this->template();
        $monthlyScores = $this->monthlyScores();
        $completion = $this->completion();
        $monitoringSummary = $this->monitoringSummary();
    @endphp

    <div class="daily-activity daily-activity--home">
        <section class="daily-activity__toolbar" aria-label="Pengaturan aktivitas rumah">
            <div class="daily-activity__filters">
                @if ($this->isParent())
                    <label class="daily-activity__field">
                        <span>Siswa</span>
                        <select wire:model.live="selectedStudentId" @disabled(count($students) <= 1)>
                            @forelse ($students as $student)
                                <option value="{{ $student['id'] }}">
                                    {{ $student['name'] }} - {{ $student['class_name'] }}
                                </option>
                            @empty
                                <option value="">Belum ada anak terhubung</option>
                            @endforelse
                        </select>
                    </label>
                @else
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
                @endif

                <label class="daily-activity__field">
                    <span>Hari dan Tanggal</span>
                    <input type="date" max="{{ today()->toDateString() }}" wire:model.live="selectedDate">
                </label>
            </div>

            <div class="daily-activity__toolbar-copy">
                <span>{{ $this->formattedDate() }}</span>
                @if ($this->isParent())
                    <strong>{{ $completion['checked'] }} dari {{ $completion['total'] }} dilakukan</strong>
                @else
                    <strong>{{ $monitoringSummary['submitted'] }} dari {{ $monitoringSummary['total'] }} sudah mengisi</strong>
                @endif
            </div>

            <x-filament::button
                tag="a"
                :href="$this->dailyReportUrl()"
                target="_blank"
                color="gray"
                icon="gmdi-picture-as-pdf-o"
                :disabled="$this->isParent() ? ! $selectedStudentId : ! $selectedClassId"
            >
                Export PDF
            </x-filament::button>

            @if ($this->isParent())
                <x-filament::button
                    type="button"
                    icon="gmdi-save-o"
                    wire:click="saveChecklist"
                    wire:loading.attr="disabled"
                    :disabled="! $selectedStudentId"
                >
                    Simpan Aktivitas
                </x-filament::button>
            @endif
        </section>

        <section class="daily-activity__scores" aria-label="Rekap nilai aktivitas rumah bulan ini">
            @foreach ($monthlyScores as $score)
                <div class="daily-activity__score daily-activity__score--{{ $score['color'] }}">
                    <div>
                        <span>{{ $score['category'] }}</span>
                        <strong>Nilai {{ $score['rating'] }}</strong>
                    </div>
                    <div class="daily-activity__progress" aria-label="{{ $score['percentage'] }} persen">
                        <i style="width: {{ $score['percentage'] }}%"></i>
                    </div>
                    <small>{{ $this->monthlyScorePeriod() }} · {{ $score['rating_label'] }} · {{ $score['percentage'] }}% · skor {{ $score['score'] }}/{{ $score['maximum_score'] }}</small>
                </div>
            @endforeach
        </section>

        @if ($this->isParent())
            <section class="daily-activity__workspace">
                <header class="daily-activity__workspace-header">
                    <div>
                        <h2>Checklist Aktivitas Harian</h2>
                        <p>Centang aktivitas yang benar-benar sudah dilakukan. Daftar mengikuti Buku Penghubung dan tidak dapat diubah.</p>
                    </div>
                    <div class="daily-activity__bulk-actions daily-activity__bulk-actions--wide">
                        <button type="button" wire:click="markAll(true)">Centang semua</button>
                        <button type="button" wire:click="markAll(false)">Kosongkan</button>
                    </div>
                </header>

                @if (empty($students))
                    <div class="daily-activity__empty">
                        <x-filament::icon icon="gmdi-family-restroom-o" />
                        <h3>Belum ada anak yang terhubung</h3>
                        <p>Hubungi wali kelas atau admin sekolah untuk menghubungkan akun orang tua.</p>
                    </div>
                @else
                    <div class="daily-activity__home-checklist">
                        @foreach ($template as $group)
                            <fieldset>
                                <legend>
                                    <span>{{ $group['category'] }}</span>
                                    <small>{{ collect($group['items'])->filter(fn (array $item): bool => $checks[$item['key']] ?? false)->count() }}/{{ count($group['items']) }}</small>
                                </legend>

                                <div>
                                    @foreach ($group['items'] as $item)
                                        <label @class(['is-checked' => $checks[$item['key']] ?? false])>
                                            <input type="checkbox" wire:model.live="checks.{{ $item['key'] }}">
                                            <span>
                                                <x-filament::icon :icon="($checks[$item['key']] ?? false) ? 'gmdi-check-circle-o' : 'gmdi-radio-button-unchecked-o'" />
                                            </span>
                                            <strong>{{ $item['label'] }}</strong>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <label class="daily-activity__note daily-activity__note--home">
                        <span>Informasi / Catatan Orang Tua</span>
                        <textarea
                            rows="4"
                            wire:model="note"
                            placeholder="Tambahkan informasi penting tentang aktivitas anak bila diperlukan..."
                        ></textarea>
                    </label>
                @endif
            </section>
        @else
            <section class="daily-activity__workspace">
                <header class="daily-activity__workspace-header">
                    <div>
                        <h2>Pemantauan Aktivitas Rumah</h2>
                        <p>Orang tua mengisi checklist langsung dari akun masing-masing. Guru hanya memantau hasilnya.</p>
                    </div>
                    <div class="daily-activity__monitoring-counts">
                        <span class="is-success">{{ $monitoringSummary['submitted'] }} sudah mengisi</span>
                        <span class="is-warning">{{ $monitoringSummary['pending'] }} belum mengisi</span>
                    </div>
                </header>

                <div class="daily-activity__monitoring">
                    @forelse ($monitoring as $student)
                        <article @class(['is-submitted' => $student['submitted']])>
                            <span class="daily-activity__avatar">{{ $student['initials'] }}</span>
                            <div>
                                <strong>{{ $student['name'] }}</strong>
                                <small>{{ $student['nis'] }}</small>
                            </div>
                            <div class="daily-activity__monitoring-progress">
                                <span>{{ $student['checked'] }}/{{ $student['total'] }} aktivitas</span>
                                <i><b style="width: {{ $student['total'] ? round(($student['checked'] / $student['total']) * 100) : 0 }}%"></b></i>
                            </div>
                            <div class="daily-activity__submission-status">
                                @if ($student['submitted'])
                                    <span class="is-success">
                                        <x-filament::icon icon="gmdi-check-circle-o" />
                                        Diisi {{ $student['submitted_at'] }}
                                    </span>
                                @else
                                    <span class="is-pending">
                                        <x-filament::icon icon="gmdi-schedule-o" />
                                        Belum diisi
                                    </span>
                                @endif
                            </div>
                            @if ($student['record_url'])
                                <x-filament::button
                                    tag="a"
                                    :href="$student['record_url']"
                                    color="gray"
                                    size="sm"
                                    icon="gmdi-visibility-o"
                                >
                                    Lihat
                                </x-filament::button>
                            @endif
                        </article>
                    @empty
                        <div class="daily-activity__empty">
                            <x-filament::icon icon="gmdi-group-off-o" />
                            <h3>Belum ada siswa aktif</h3>
                            <p>Tambahkan siswa aktif ke kelas ini agar status pengisian dapat dipantau.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
