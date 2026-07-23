<x-filament-panels::page>
    <section class="arrival-desk">
        <label class="arrival-desk__search" for="arrival-search">
            <x-filament::icon icon="gmdi-search-o" />
            <span class="sr-only">Cari siswa</span>
            <input id="arrival-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama atau NIS siswa...">
        </label>

        <div class="arrival-desk__summary" aria-live="polite">
            <span>Belum hadir: <strong>{{ $this->waitingStudents->count() }}</strong></span>
            <span>Sudah hadir: <strong>{{ $this->attendedStudents->count() }}</strong></span>
        </div>

        <div class="arrival-desk__lists">
            <section class="arrival-desk__panel">
                <header><div><h2>Belum hadir</h2><p>Pilih siswa yang baru tiba.</p></div></header>
                <div class="arrival-desk__rows">
                    @forelse ($this->waitingStudents as $student)
                        <article class="arrival-desk__student" wire:key="waiting-{{ $student->id }}">
                            <div><strong>{{ $student->name }}</strong><span>{{ $student->nis }} &middot; {{ $student->class?->name ?? 'Belum ada kelas' }}</span></div>
                            <button type="button" wire:click="markPresent({{ $student->id }})" wire:loading.attr="disabled" wire:target="markPresent({{ $student->id }})">Hadir</button>
                        </article>
                    @empty
                        <p class="arrival-desk__empty">Semua siswa pada hasil pencarian sudah tercatat hadir.</p>
                    @endforelse
                </div>
            </section>

            <section class="arrival-desk__panel arrival-desk__panel--present">
                <header><div><h2>Sudah hadir</h2><p>Data hari ini tersimpan otomatis.</p></div></header>
                <div class="arrival-desk__rows">
                    @forelse ($this->attendedStudents as $arrival)
                        <article class="arrival-desk__student" wire:key="present-{{ $arrival->id }}">
                            <div><strong>{{ $arrival->student?->name }}</strong><span>{{ $arrival->schoolClass?->name ?? 'Belum ada kelas' }} &middot; {{ substr($arrival->arrival_time, 0, 5) }}</span></div>
                            <span @class(['arrival-desk__status', 'arrival-desk__status--late' => $arrival->status === 'late'])>{{ $arrival->status === 'late' ? 'Terlambat' : 'Tepat waktu' }}</span>
                        </article>
                    @empty
                        <p class="arrival-desk__empty">Belum ada siswa yang dicatat hadir hari ini.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-filament-panels::page>
