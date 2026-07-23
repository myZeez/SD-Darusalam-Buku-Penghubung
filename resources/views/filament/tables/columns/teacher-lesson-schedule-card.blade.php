@php
    $record = $getRecord();
    $lessonPeriod = $record->lessonPeriod;
    $subject = $record->teachingAssignment?->subject;
@endphp

<article class="teacher-schedule-card">
    <div class="teacher-schedule-card__header">
        <div class="teacher-schedule-card__subject">
            <h3>{{ $subject?->name ?? 'Mata pelajaran belum ditentukan' }}</h3>
            <p>{{ $subject?->code ?? '-' }}</p>
        </div>

        <div class="teacher-schedule-card__time" aria-label="Waktu pelajaran">
            <span>{{ $lessonPeriod?->name ?? 'Jam pelajaran' }}</span>
            <strong>
                {{ substr($lessonPeriod?->start_time ?? '', 0, 5) ?: '-' }}–{{ substr($lessonPeriod?->end_time ?? '', 0, 5) ?: '-' }}
            </strong>
        </div>
    </div>
</article>
