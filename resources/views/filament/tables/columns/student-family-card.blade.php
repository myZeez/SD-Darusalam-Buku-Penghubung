@php
    use App\Filament\Resources\Students\StudentResource;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $record = $getRecord();
    $isStudentSelf = auth()->user()?->hasRole('siswa') ?? false;
    $photo = $record->photo ?: $record->user?->avatar;
    $initials = Str::of($record->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
        ->implode('');
@endphp

@once
    <style>
        .fi-ta-table:has(.student-family-card) {
            display: block;
            width: 100%;
        }

        .fi-ta-table:has(.student-family-card) thead {
            display: none;
        }

        .fi-ta-table:has(.student-family-card) tbody {
            display: grid;
            width: 100%;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .fi-ta-table:has(.student-family-card) tr,
        .fi-ta-table:has(.student-family-card) .fi-ta-cell {
            display: block;
            width: 100%;
            min-width: 0;
        }

        .student-family-card {
            display: flex;
            width: 100%;
            min-width: 0;
            min-height: 18rem;
            box-sizing: border-box;
            flex-direction: column;
            gap: 1.25rem;
            padding: 1.25rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
        }

        .dark .student-family-card {
            border-color: rgb(255 255 255 / 0.1);
            background: #18181b;
        }

        .student-family-card__header,
        .student-family-card__status,
        .student-family-card__actions {
            display: flex;
            align-items: center;
        }

        .student-family-card__header {
            align-items: flex-start;
            gap: 1rem;
        }

        .student-family-card__avatar {
            display: grid;
            width: 3.5rem;
            height: 3.5rem;
            flex: 0 0 3.5rem;
            place-items: center;
            overflow: hidden;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: #9a3412;
            background: #fff7ed;
            font-size: 1rem;
            font-weight: 700;
        }

        .dark .student-family-card__avatar {
            border-color: rgb(249 115 22 / 0.3);
            color: #fdba74;
            background: rgb(249 115 22 / 0.1);
        }

        .student-family-card__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-family-card__identity {
            min-width: 0;
            flex: 1;
        }

        .student-family-card__name {
            margin: 0;
            overflow: hidden;
            color: #111827;
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1.5rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .student-family-card__name,
        .dark .student-family-card__value {
            color: #ffffff;
        }

        .student-family-card__nis {
            margin: 0.25rem 0 0;
            color: #6b7280;
            font-size: 0.8125rem;
            line-height: 1.25rem;
        }

        .dark .student-family-card__nis,
        .dark .student-family-card__label {
            color: #9ca3af;
        }

        .student-family-card__status {
            width: max-content;
            margin-top: 0.5rem;
            gap: 0.375rem;
            color: #047857;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .student-family-card__status::before {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: currentColor;
            content: '';
        }

        .student-family-card__status--inactive {
            color: #b91c1c;
        }

        .dark .student-family-card__status {
            color: #6ee7b7;
        }

        .dark .student-family-card__status--inactive {
            color: #fca5a5;
        }

        .student-family-card__details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin: 0;
        }

        .student-family-card__label {
            margin: 0;
            color: #6b7280;
            font-size: 0.75rem;
            line-height: 1rem;
        }

        .student-family-card__value {
            margin: 0.25rem 0 0;
            overflow-wrap: anywhere;
            color: #111827;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25rem;
        }

        .student-family-card__actions {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
        }

        .dark .student-family-card__actions {
            border-top-color: rgb(255 255 255 / 0.1);
        }

        @media (min-width: 768px) {
            .fi-ta-table:has(.student-family-card) tbody {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 420px) {
            .student-family-card {
                min-height: 0;
                gap: 1rem;
                padding: 1rem;
            }

            .student-family-card__header {
                gap: 0.75rem;
            }

            .student-family-card__avatar {
                width: 3rem;
                height: 3rem;
                flex-basis: 3rem;
            }

            .student-family-card__name {
                display: -webkit-box;
                overflow: hidden;
                text-overflow: clip;
                white-space: normal;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .student-family-card__details {
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
            }

            .student-family-card__actions .fi-btn {
                width: 100%;
                min-height: 2.75rem;
            }
        }

        @media (min-width: 1440px) {
            .fi-ta-table:has(.student-family-card) tbody {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
@endonce

<article class="student-family-card">
    <header class="student-family-card__header">
        <div class="student-family-card__avatar" aria-hidden="true">
            @if ($photo)
                <img src="{{ Storage::url($photo) }}" alt="">
            @else
                {{ $initials ?: 'S' }}
            @endif
        </div>

        <div class="student-family-card__identity">
            <h3 class="student-family-card__name">{{ $record->name }}</h3>
            <p class="student-family-card__nis">NIS {{ $record->nis }}</p>
            <span @class([
                'student-family-card__status',
                'student-family-card__status--inactive' => $record->status !== 'active',
            ])>
                {{ $record->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
            </span>
        </div>
    </header>

    <dl class="student-family-card__details">
        <div>
            <dt class="student-family-card__label">Kelas</dt>
            <dd class="student-family-card__value">{{ $record->class?->name ?? 'Belum ditempatkan' }}</dd>
        </div>
        <div>
            <dt class="student-family-card__label">Wali Kelas</dt>
            <dd class="student-family-card__value">{{ $record->class?->teacher?->user?->name ?? 'Belum ditentukan' }}</dd>
        </div>
        <div>
            <dt class="student-family-card__label">Tahun Ajaran</dt>
            <dd class="student-family-card__value">{{ $record->class?->academic_year ?? '-' }}</dd>
        </div>
        <div>
            <dt class="student-family-card__label">Ruang</dt>
            <dd class="student-family-card__value">{{ $record->class?->room ?: 'Belum ditentukan' }}</dd>
        </div>
    </dl>

    <div class="student-family-card__actions">
        <x-filament::button
            :href="StudentResource::getUrl('view', ['record' => $record])"
            tag="a"
            icon="heroicon-o-eye"
            size="sm"
        >
            {{ $isStudentSelf ? 'Lihat Profil Saya' : 'Lihat Profil Anak' }}
        </x-filament::button>
    </div>
</article>
