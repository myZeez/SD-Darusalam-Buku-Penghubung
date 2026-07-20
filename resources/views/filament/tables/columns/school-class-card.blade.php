@php
    use App\Filament\Resources\SchoolClasses\SchoolClassResource;

    $record = $getRecord();
    $studentCount = (int) ($record->students_count ?? 0);
    $capacity = max((int) $record->capacity, 1);
    $percentage = min(100, (int) round(($studentCount / $capacity) * 100));
@endphp

<div class="school-class-card">
    <div class="school-class-card__header">
        <div class="school-class-card__identity">
            <p class="school-class-card__eyebrow">
                Tingkat {{ $record->grade_level ?? '-' }} &middot; {{ $record->academic_year }}
            </p>
            <h3 class="school-class-card__title">
                {{ $record->name }}
            </h3>
        </div>

        <span class="school-class-card__capacity">
            {{ $studentCount }}/{{ $capacity }} siswa
        </span>
    </div>

    <div>
        <div class="school-class-card__progress-label">
            <span>Kapasitas kelas</span>
            <span>{{ $percentage }}%</span>
        </div>
        <div class="school-class-card__progress-track">
            <div class="school-class-card__progress-bar" style="width: {{ $percentage }}%"></div>
        </div>
    </div>

    <dl class="school-class-card__details">
        <div>
            <dt class="school-class-card__term">Guru Utama</dt>
            <dd class="school-class-card__value">{{ $record->teacher?->user?->name ?? 'Belum ditentukan' }}</dd>
        </div>
        <div>
            <dt class="school-class-card__term">Guru Pendamping</dt>
            <dd class="school-class-card__value">{{ $record->assistantTeacher?->user?->name ?? 'Belum ditentukan' }}</dd>
        </div>
        <div>
            <dt class="school-class-card__term">Ruang</dt>
            <dd class="school-class-card__value">{{ $record->room ?: 'Belum ditentukan' }}</dd>
        </div>
    </dl>

    <div class="school-class-card__actions">
        <x-filament::button
            :href="SchoolClassResource::getUrl('view', ['record' => $record])"
            tag="a"
            icon="heroicon-o-eye"
            size="sm"
        >
            Lihat Detail
        </x-filament::button>

        @if (SchoolClassResource::canEdit($record))
            <x-filament::button
                :href="SchoolClassResource::getUrl('edit', ['record' => $record])"
                tag="a"
                color="gray"
                icon="heroicon-o-pencil-square"
                size="sm"
            >
                Ubah
            </x-filament::button>
        @endif
    </div>
</div>
