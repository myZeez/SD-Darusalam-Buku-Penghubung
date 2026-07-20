@php
    use App\Filament\Resources\ActivityComments\ActivityCommentResource;
    use App\Models\SchoolActivity;
    use Illuminate\Support\Str;

    $record = $getRecord();
    $viewer = auth()->user();
    $student = $record->activity?->student;
    $latestMessage = $record->latestReply ?? $record;
    $isParent = $viewer?->hasRole('orang_tua') ?? false;

    $contactName = $isParent
        ? ($student?->class?->teacher?->user?->name ?? 'Wali Kelas')
        : ($student?->parent?->user?->name ?? 'Orang Tua '.$student?->name);
    $contactRole = $isParent ? 'Wali kelas' : 'Orang tua';
    $initials = Str::of($contactName)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
        ->implode('');
    $messagePreview = Str::limit(preg_replace('/\s+/', ' ', $latestMessage->comment), 105);
    $messageSender = $latestMessage->user_id === $viewer?->id
        ? 'Anda'
        : ($latestMessage->user?->name ?? 'Pengguna');
    $activityLabel = $record->activity_type === SchoolActivity::class
        ? 'Laporan sekolah'
        : 'Laporan rumah';
    $messageCount = ((int) ($record->replies_count ?? 0)) + 1;
@endphp

<a
    class="activity-conversation"
    href="{{ ActivityCommentResource::getUrl('view', ['record' => $record]) }}"
    aria-label="Buka percakapan dengan {{ $contactName }} tentang {{ $student?->name ?? 'murid' }}"
>
    <span class="activity-conversation__avatar" aria-hidden="true">
        {{ $initials ?: 'OT' }}
    </span>

    <span class="activity-conversation__content">
        <span class="activity-conversation__identity">
            <strong>{{ $contactName }}</strong>
            <span>{{ $contactRole }} {{ $student?->name ?? 'murid' }}</span>
        </span>

        <span class="activity-conversation__preview">
            <span class="activity-conversation__sender">{{ $messageSender }}:</span>
            {{ $messagePreview }}
        </span>

        <span class="activity-conversation__context">
            {{ $student?->class?->name ?? 'Kelas belum ditentukan' }}
            <span aria-hidden="true">&bull;</span>
            {{ $activityLabel }}
        </span>
    </span>

    <span class="activity-conversation__aside">
        <time datetime="{{ $latestMessage->created_at?->toAtomString() }}">
            {{ $latestMessage->created_at?->isToday()
                ? $latestMessage->created_at?->format('H:i')
                : $latestMessage->created_at?->format('d M') }}
        </time>
        <span class="activity-conversation__count" aria-label="{{ $messageCount }} pesan">
            {{ $messageCount }}
        </span>
    </span>
</a>
