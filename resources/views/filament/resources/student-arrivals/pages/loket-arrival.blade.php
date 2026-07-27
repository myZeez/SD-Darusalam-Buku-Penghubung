<x-filament-panels::page>
    <section class="arrival-desk">
        <label class="arrival-desk__search" for="arrival-search">
            <x-filament::icon icon="gmdi-search-o" />
            <span class="sr-only">Cari siswa</span>
            <input id="arrival-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama atau NIS siswa yang terlambat...">
        </label>

        <div class="arrival-desk__summary" aria-live="polite">
            <span>Tercatat terlambat hari ini: <strong>{{ $this->lateStudents->count() }}</strong></span>
        </div>

        <div class="arrival-desk__lists">
            <section class="arrival-desk__panel">
                <header><div><h2>Catat keterlambatan ⏰</h2><p>Pilih hanya siswa yang datang terlambat.</p></div></header>
                <div class="arrival-desk__rows">
                    @forelse ($this->unrecordedStudents as $student)
                        <article class="arrival-desk__student" wire:key="unrecorded-{{ $student->id }}">
                            <div><strong>{{ $student->name }}</strong><span>{{ $student->nis }} &middot; {{ $student->class?->name ?? 'Belum ada kelas' }}</span></div>
                            <button type="button" wire:click="markLate({{ $student->id }})" wire:loading.attr="disabled" wire:target="markLate({{ $student->id }})">Terlambat</button>
                        </article>
                    @empty
                        <p class="arrival-desk__empty">Tidak ada siswa lain yang perlu dicatat pada hasil pencarian.</p>
                    @endforelse
                </div>
            </section>

            <section class="arrival-desk__panel arrival-desk__panel--present">
                <header><div><h2>Sudah dicatat</h2><p>Riwayat keterlambatan hari ini.</p></div></header>
                <div class="arrival-desk__rows">
                    @forelse ($this->lateStudents as $arrival)
                        <article class="arrival-desk__student" wire:key="late-{{ $arrival->id }}">
                            <div><strong>{{ $arrival->student?->name }}</strong><span>{{ $arrival->schoolClass?->name ?? 'Belum ada kelas' }} &middot; {{ substr($arrival->arrival_time, 0, 5) }}</span></div>
                            <span class="arrival-desk__status arrival-desk__status--late">Terlambat</span>
                        </article>
                    @empty
                        <p class="arrival-desk__empty">Belum ada keterlambatan yang dicatat hari ini.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-filament-panels::page>
