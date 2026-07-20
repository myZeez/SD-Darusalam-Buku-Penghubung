@php
    $record = $getRecord();
    $response = auth()->user()?->hasRole('orang_tua') ? $record->responses->first()?->response : null;
    $responseLabel = match ($response) {
        'attending' => 'Saya Akan Datang',
        'unavailable' => 'Belum Bisa Datang',
        'reschedule' => 'Jadwalkan Ulang',
        default => 'Belum Dikonfirmasi',
    };
@endphp

<article class="schedule-card">
    <div class="schedule-card__date" aria-label="Tanggal kegiatan">
        <strong>{{ $record->activity_date->format('d') }}</strong>
        <span>{{ $record->activity_date->translatedFormat('M') }}</span>
    </div>

    <div class="schedule-card__body">
        <div class="schedule-card__heading">
            <div>
                <span>{{ $record->schoolClass?->name ?? 'Seluruh Sekolah' }}</span>
                <h3>{{ $record->title }}</h3>
            </div>

            @if (auth()->user()?->hasRole('orang_tua'))
                <span class="schedule-card__response schedule-card__response--{{ $response ?? 'empty' }}">
                    Konfirmasi Saya: {{ $responseLabel }}
                </span>
            @endif
        </div>

        @if ($record->description)
            <p class="schedule-card__description">{{ $record->description }}</p>
        @endif

        <dl class="schedule-card__meta">
            <div>
                <dt><x-filament::icon icon="gmdi-schedule-o" /> Waktu</dt>
                <dd>
                    {{ $record->start_time?->format('H:i') ?? 'Belum ditentukan' }}
                    @if ($record->end_time)
                        - {{ $record->end_time->format('H:i') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt><x-filament::icon icon="gmdi-location-on-o" /> Lokasi</dt>
                <dd>{{ $record->location ?: 'Belum ditentukan' }}</dd>
            </div>
            <div>
                <dt><x-filament::icon icon="gmdi-person-o" /> Dibuat oleh</dt>
                <dd>{{ $record->creator?->name ?? 'Sistem' }}</dd>
            </div>
        </dl>
    </div>
</article>
