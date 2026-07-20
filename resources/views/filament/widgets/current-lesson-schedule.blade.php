<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $this->getPollingInterval() => $this->getPollingInterval() ? true : null,
            ], escape: false)
            ->class(['fi-wi-current-lesson'])
    "
>
    <section class="current-lesson" aria-labelledby="current-lesson-heading" aria-live="polite">
        <header class="current-lesson__header">
            <div class="current-lesson__heading">
                <span class="current-lesson__icon" aria-hidden="true">
                    <x-filament::icon icon="gmdi-play-circle-o" />
                </span>

                <div>
                    <p class="current-lesson__eyebrow">Jadwal pelajaran</p>
                    <h2 id="current-lesson-heading">Sedang berlangsung</h2>
                </div>
            </div>

            <time class="current-lesson__time" datetime="{{ now()->format('H:i') }}">
                Diperbarui {{ $currentTime }}
            </time>
        </header>

        @if ($currentSchedules->isNotEmpty())
            <div class="current-lesson__list">
                @foreach ($currentSchedules as $schedule)
                    @php
                        $assignment = $schedule->teachingAssignment;
                        $lessonPeriod = $schedule->lessonPeriod;
                    @endphp

                    <article class="current-lesson__item">
                        <div class="current-lesson__subject">
                            <span class="current-lesson__status">Berlangsung sekarang</span>
                            <h3>{{ $assignment->subject->name }}</h3>
                        </div>

                        <dl class="current-lesson__details">
                            <div>
                                <dt><x-filament::icon icon="gmdi-class-o" /> Kelas</dt>
                                <dd>{{ $assignment->schoolClass->name }}</dd>
                            </div>
                            <div>
                                <dt><x-filament::icon icon="gmdi-schedule-o" /> Waktu</dt>
                                <dd>{{ $lessonPeriod->name }} · {{ \Illuminate\Support\Carbon::parse($lessonPeriod->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($lessonPeriod->end_time)->format('H:i') }}</dd>
                            </div>
                            <div>
                                <dt><x-filament::icon icon="gmdi-person-o" /> Guru</dt>
                                <dd>{{ $assignment->teacher->user->name }}</dd>
                            </div>
                            @if (filled($schedule->room))
                                <div>
                                    <dt><x-filament::icon icon="gmdi-location-on-o" /> Ruang</dt>
                                    <dd>{{ $schedule->room }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>
                @endforeach
            </div>
        @else
            <div class="current-lesson__empty">
                <x-filament::icon icon="gmdi-schedule-o" aria-hidden="true" />
                <div>
                    <p>Tidak ada pelajaran yang sedang berlangsung saat ini.</p>
                    <span>Jadwal akan tampil otomatis sesuai kelas Anda.</span>
                </div>
            </div>
        @endif
    </section>
</x-filament-widgets::widget>
