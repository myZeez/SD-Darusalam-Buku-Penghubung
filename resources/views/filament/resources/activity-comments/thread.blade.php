@php
    use App\Filament\Resources\ActivityComments\ActivityCommentResource;
    use App\Models\SchoolActivity;
    use Illuminate\Support\Str;

    $activity = $thread->activity;
    $student = $activity?->student;
    $viewer = auth()->user();
    $messages = collect([$thread])->concat($thread->replies)->sortBy('created_at')->values();
    $canReply = ActivityCommentResource::canCreate() && ActivityCommentResource::canView($thread) && ! $thread->isClosed();
    $canClose = ! $thread->isClosed() && ($viewer?->isAdmin() || $thread->user_id === $viewer?->id);
    $category = match ($thread->category) {
        'learning' => 'Pelajaran',
        'behaviour' => 'Sikap & Perkembangan',
        'attendance' => 'Kehadiran',
        default => 'Lainnya',
    };
@endphp

<section class="discussion-topic" aria-label="Topik diskusi {{ $thread->topic }}">
    <header class="discussion-topic__header">
        <div>
            <span class="discussion-topic__category">{{ $category }}</span>
            <h2>{{ $thread->topic ?: 'Topik diskusi' }}</h2>
            <p>
                {{ $student?->name ?? 'Siswa terkait' }} &middot; {{ $student?->class?->name ?? 'Kelas belum ditentukan' }}
                &middot; {{ $thread->activity_type === SchoolActivity::class ? 'Laporan sekolah' : 'Laporan rumah' }}
            </p>
        </div>

        <div class="discussion-topic__status">
            @if ($thread->isClosed())
                <span class="discussion-topic__status-badge discussion-topic__status-badge--closed">Ditutup</span>
                <small>{{ $thread->closed_at?->translatedFormat('d M Y, H:i') }}</small>
            @else
                <span class="discussion-topic__status-badge">Berjalan</span>
                @if ($canClose)
                    <button type="button" wire:click="closeDiscussion" wire:confirm="Tutup diskusi ini? Topik tidak dapat dibuka kembali.">
                        Tutup diskusi
                    </button>
                @endif
            @endif
        </div>
    </header>

    <div class="discussion-topic__history" aria-live="polite">
        <h3>Riwayat tanggapan</h3>
        @foreach ($messages as $index => $message)
            @php
                $role = match ($message->user?->getRoleNames()->first()) {
                    'super_admin', 'admin' => 'Admin',
                    'kepala_sekolah' => 'Kepala Sekolah',
                    'guru' => 'Guru',
                    'orang_tua' => 'Orang Tua',
                    default => 'Pengguna',
                };
            @endphp
            <article class="discussion-entry">
                <div class="discussion-entry__marker" aria-hidden="true">{{ $index + 1 }}</div>
                <div class="discussion-entry__content">
                    <header>
                        <div>
                            <strong>{{ $message->user_id === $viewer?->id ? 'Anda' : ($message->user?->name ?? 'Pengguna') }}</strong>
                            <span>{{ $role }}</span>
                        </div>
                        <time datetime="{{ $message->created_at?->toAtomString() }}">{{ $message->created_at?->translatedFormat('d M Y, H:i') }}</time>
                    </header>
                    <div class="discussion-entry__body">{!! nl2br(e($message->comment)) !!}</div>
                    @if (! $thread->isClosed() && ActivityCommentResource::canEdit($message))
                        <button type="button" class="discussion-entry__edit" wire:click="mountAction('editMessage', { message: {{ $message->getKey() }} })">Ubah tanggapan</button>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if ($canReply)
        <form class="discussion-topic__reply" wire:submit="sendReply">
            <label for="discussion-reply-message">Tambahkan tanggapan</label>
            <textarea id="discussion-reply-message" wire:model="replyMessage" rows="4" maxlength="5000" placeholder="Tambahkan informasi atau tindak lanjut untuk topik ini..."></textarea>
            @error('replyMessage') <p class="discussion-topic__error">{{ $message }}</p> @enderror
            <div><button type="submit" wire:loading.attr="disabled" wire:target="sendReply">Simpan tanggapan</button></div>
        </form>
    @else
        <p class="discussion-topic__closed-note">Diskusi sudah ditutup. Riwayat tetap dapat dibaca, tetapi tanggapan baru tidak dapat ditambahkan.</p>
    @endif
</section>
