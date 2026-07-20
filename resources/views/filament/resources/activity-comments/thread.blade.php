@php
    use App\Filament\Resources\ActivityComments\ActivityCommentResource;
    use App\Models\SchoolActivity;
    use Illuminate\Support\Str;

    $activity = $thread->activity;
    $student = $activity?->student;
    $viewer = auth()->user();
    $messages = collect([$thread])->concat($thread->replies)->sortBy('created_at')->values();
    $isParent = $viewer?->hasRole('orang_tua') ?? false;
    $canReply = ActivityCommentResource::canCreate() && ActivityCommentResource::canView($thread);

    $contactName = $isParent
        ? ($student?->class?->teacher?->user?->name ?? 'Wali Kelas')
        : ($student?->parent?->user?->name ?? 'Orang Tua '.$student?->name);
    $contactRole = $isParent ? 'Wali kelas' : 'Orang tua';
    $contactInitials = Str::of($contactName)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
        ->implode('');
    $replyingTo = $messages->firstWhere('id', (int) $replyingToId);
    $previousDate = null;
@endphp

<section class="discussion-chat" aria-label="Percakapan dengan {{ $contactName }}">
    <header class="discussion-chat__header">
        <div class="discussion-chat__contact-avatar" aria-hidden="true">
            {{ $contactInitials ?: 'OT' }}
        </div>

        <div class="discussion-chat__identity">
            <h2>{{ $contactName }}</h2>
            <p>
                {{ $contactRole }} {{ $student?->name ?? 'murid' }}
                <span aria-hidden="true">&bull;</span>
                {{ $student?->class?->name ?? 'Kelas belum ditentukan' }}
            </p>
        </div>

        <div class="discussion-chat__context">
            <span>{{ $thread->activity_type === SchoolActivity::class ? 'Laporan sekolah' : 'Laporan rumah' }}</span>
            <time datetime="{{ $activity?->activity_date?->toDateString() }}">
                {{ $activity?->activity_date?->format('d M Y') ?? 'Tanpa tanggal' }}
            </time>
        </div>
    </header>

    <div class="discussion-chat__messages" aria-live="polite">
        @foreach ($messages as $message)
            @php
                $isOutgoing = $message->user_id === $viewer?->id;
                $messageDate = $message->created_at?->toDateString();
                $showDate = $messageDate !== $previousDate;
                $previousDate = $messageDate;
                $role = match ($message->user?->getRoleNames()->first()) {
                    'super_admin' => 'Admin',
                    'kepala_sekolah' => 'Kepala Sekolah',
                    'guru' => 'Guru',
                    'orang_tua' => 'Orang Tua',
                    default => 'Pengguna',
                };
                $senderName = $isOutgoing ? 'Anda' : ($message->user?->name ?? 'Pengguna');
                $senderInitials = Str::of($message->user?->name ?? 'P')
                    ->explode(' ')
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                    ->implode('');
            @endphp

            @if ($showDate)
                <div class="discussion-chat__date">
                    <time datetime="{{ $messageDate }}">
                        {{ $message->created_at?->isToday()
                            ? 'Hari ini'
                            : $message->created_at?->translatedFormat('d F Y') }}
                    </time>
                </div>
            @endif

            <article @class([
                'discussion-message',
                'discussion-message--outgoing' => $isOutgoing,
                'discussion-message--incoming' => ! $isOutgoing,
            ])>
                @unless ($isOutgoing)
                    <div class="discussion-message__avatar" aria-hidden="true">
                        {{ $senderInitials ?: 'P' }}
                    </div>
                @endunless

                <div class="discussion-message__bubble">
                    <header class="discussion-message__sender">
                        <strong>{{ $senderName }}</strong>
                        <span>{{ $role }}</span>
                    </header>

                    <div class="discussion-message__body">{!! nl2br(e($message->comment)) !!}</div>

                    <footer class="discussion-message__footer">
                        <time datetime="{{ $message->created_at?->toAtomString() }}">
                            {{ $message->created_at?->format('H:i') }}
                        </time>

                        <div class="discussion-message__actions">
                            @if ($canReply)
                                <button
                                    type="button"
                                    class="discussion-message__action"
                                    wire:click="selectReply({{ $message->getKey() }})"
                                    x-on:click="setTimeout(() => document.getElementById('discussion-reply-message')?.focus(), 120)"
                                >
                                    <x-filament::icon icon="gmdi-reply-o" />
                                    <span>Balas</span>
                                </button>
                            @endif

                            @if (ActivityCommentResource::canEdit($message))
                                <button
                                    type="button"
                                    class="discussion-message__action"
                                    wire:click="mountAction('editMessage', { message: {{ $message->getKey() }} })"
                                >
                                    <x-filament::icon icon="gmdi-edit-o" />
                                    <span>Ubah</span>
                                </button>
                            @endif
                        </div>
                    </footer>
                </div>
            </article>
        @endforeach
    </div>

    @if ($canReply)
        <form class="discussion-composer" wire:submit="sendReply">
            @if ($replyingTo)
                <div class="discussion-composer__replying">
                    <div>
                        <span>Membalas {{ $replyingTo->user_id === $viewer?->id ? 'pesan Anda' : ($replyingTo->user?->name ?? 'pesan') }}</span>
                        <p>{{ Str::limit(preg_replace('/\s+/', ' ', $replyingTo->comment), 120) }}</p>
                    </div>
                    <button type="button" wire:click="cancelReply" title="Batalkan balasan">
                        <x-filament::icon icon="gmdi-close-o" />
                        <span class="sr-only">Batalkan balasan</span>
                    </button>
                </div>
            @endif

            <div class="discussion-composer__field">
                <label class="sr-only" for="discussion-reply-message">Tulis pesan</label>
                <textarea
                    id="discussion-reply-message"
                    wire:model="replyMessage"
                    rows="2"
                    maxlength="5000"
                    placeholder="Tulis pesan untuk {{ $contactName }}..."
                ></textarea>
                <button
                    type="submit"
                    class="discussion-composer__send"
                    title="Kirim pesan"
                    wire:loading.attr="disabled"
                    wire:target="sendReply"
                >
                    <x-filament::icon icon="gmdi-send-o" />
                    <span class="sr-only">Kirim pesan</span>
                </button>
            </div>

            @error('replyMessage')
                <p class="discussion-composer__error">{{ $message }}</p>
            @enderror
        </form>
    @endif
</section>
