@php
    use App\Filament\Resources\LessonSchedules\LessonScheduleResource;

    $record = $getRecord();
    $lessonPeriod = $record->lessonPeriod;
    $assignment = $record->teachingAssignment;
    $subject = $assignment?->subject;
    $class = $assignment?->schoolClass;
@endphp

<article class="teacher-schedule-card">
    <div class="teacher-schedule-card__header">
        <div class="teacher-schedule-card__subject">
            <span class="teacher-schedule-card__day">{{ $record->day_label }}</span>
            <h3>{{ $subject?->name ?? 'Mata pelajaran belum ditentukan' }}</h3>
            <p>{{ $subject?->code ?? '—' }}</p>
        </div>

        <div class="teacher-schedule-card__time" aria-label="Waktu mengajar">
            <span>{{ $lessonPeriod?->name ?? 'Jam pelajaran' }}</span>
            <strong>
                {{ substr($lessonPeriod?->start_time ?? '', 0, 5) ?: '—' }}–{{ substr($lessonPeriod?->end_time ?? '', 0, 5) ?: '—' }}
            </strong>
        </div>
    </div>

    <dl class="teacher-schedule-card__details">
        <div>
            <dt><x-filament::icon icon="gmdi-class-o" /> Kelas</dt>
            <dd>{{ $class?->name ?? 'Belum ditentukan' }}</dd>
        </div>
        <div>
            <dt><x-filament::icon icon="gmdi-location-on-o" /> Ruang</dt>
            <dd>{{ $record->room ?: ($class?->room ?: 'Mengikuti ruang kelas') }}</dd>
        </div>
    </dl>

    <div class="teacher-schedule-card__actions">
        <x-filament::button
            :href="LessonScheduleResource::getUrl('view', ['record' => $record])"
            tag="a"
            color="gray"
            icon="heroicon-o-eye"
            size="sm"
        >
            Lihat Detail
        </x-filament::button>
    </div>
</article>
