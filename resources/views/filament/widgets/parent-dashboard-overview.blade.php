<x-filament-widgets::widget class="fi-wi-parent-overview">
    <section class="parent-overview" aria-labelledby="parent-overview-heading">
        <header class="parent-overview__header">
            <div>
                <p>Beranda orang tua</p>
                <h2 id="parent-overview-heading">Ringkasan keluarga</h2>
            </div>

        </header>

        <div class="parent-overview__body">
            <section class="parent-overview__profile" aria-labelledby="parent-profile-heading">
                <div class="parent-overview__section-heading">
                    <span class="parent-overview__section-icon" aria-hidden="true">
                        <x-filament::icon icon="gmdi-family-restroom-o" />
                    </span>
                    <div>
                        <h3 id="parent-profile-heading">Profil orang tua</h3>
                        <p>{{ $profileName }}</p>
                    </div>
                </div>

                <p class="parent-overview__contact">
                    <x-filament::icon icon="gmdi-phone-o" />
                    {{ $profilePhone ?: 'Nomor telepon belum diisi' }}
                </p>

                <ul class="parent-overview__children">
                    @forelse ($children as $child)
                        <li>
                            <span class="parent-overview__child-icon" aria-hidden="true"><x-filament::icon icon="gmdi-badge-o" /></span>
                            <div>
                                <strong>{{ $child->name }}</strong>
                                <span>{{ $child->class?->name ?? 'Belum ada kelas' }} · {{ $child->class?->teacher?->user?->name ?? 'Guru belum ditentukan' }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="parent-overview__children-empty">Belum ada anak aktif yang terhubung ke akun ini.</li>
                    @endforelse
                </ul>

                <a class="parent-overview__profile-link" href="{{ $profileUrl }}">
                    Kelola profil orang tua <x-filament::icon icon="gmdi-edit-o" />
                </a>
            </section>

            <section class="parent-overview__activity" aria-labelledby="parent-activity-heading">
                <div class="parent-overview__section-heading">
                    <span class="parent-overview__section-icon" aria-hidden="true">
                        <x-filament::icon icon="gmdi-assignment-o" />
                    </span>
                    <div>
                        <h3 id="parent-activity-heading">Rekap aktivitas</h3>
                        <p>Catatan sekolah dan rumah dalam 7 hari terakhir.</p>
                    </div>
                </div>

                <div class="parent-overview__metrics">
                    <div>
                        <x-filament::icon icon="gmdi-assignment-o" />
                        <span>Laporan sekolah</span>
                        <strong>{{ $schoolActivityCount }}</strong>
                    </div>
                    <div>
                        <x-filament::icon icon="gmdi-home-work-o" />
                        <span>Aktivitas rumah</span>
                        <strong>{{ $homeActivityCount }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <section class="parent-overview__aspects" aria-labelledby="parent-aspects-heading">
            <div class="parent-overview__aspects-heading">
                <div>
                    <h3 id="parent-aspects-heading">Empat aspek perkembangan</h3>
                    <p>Terlihat dari catatan yang dibagikan sekolah dan keluarga.</p>
                </div>
                <x-filament::icon icon="gmdi-groups-o" aria-hidden="true" />
            </div>

            <div class="parent-overview__aspects-list">
                @foreach ($developmentAspects as $aspect)
                    <article class="parent-overview__aspect">
                        <x-filament::icon :icon="$aspect['icon']" />
                        <div>
                            <h4>{{ $aspect['label'] }}</h4>
                            <p>{{ $aspect['description'] }}</p>
                        </div>
                        <span>{{ $aspect['observations'] ? $aspect['observations'].' catatan' : 'Belum ada catatan' }}</span>
                    </article>
                @endforeach
            </div>
        </section>
    </section>
</x-filament-widgets::widget>
