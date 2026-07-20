@php($credentials = session()->pull('registration_credentials'))

@if ($credentials)
    <script>
        window.downloadRegistrationCredentials = function (credentials, format) {
            const canvas = document.createElement('canvas');
            canvas.width = 1200;
            canvas.height = 800;
            const context = canvas.getContext('2d');
            const isJpeg = format === 'jpeg';

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = '#f59e0b';
            context.fillRect(0, 0, canvas.width, 18);

            context.fillStyle = '#18181b';
            context.font = '700 42px Arial, sans-serif';
            context.fillText('Buku Penghubung Digital', 72, 92);
            context.font = '400 24px Arial, sans-serif';
            context.fillStyle = '#52525b';
            context.fillText('SD Islam Darussalam', 72, 132);

            const drawAccount = (top, title, name, email, password) => {
                context.fillStyle = '#f4f4f5';
                context.fillRect(72, top, 1056, 190);
                context.fillStyle = '#18181b';
                context.font = '700 28px Arial, sans-serif';
                context.fillText(title, 104, top + 48);
                context.font = '400 23px Arial, sans-serif';
                context.fillStyle = '#3f3f46';
                context.fillText('Nama: ' + name, 104, top + 95);
                context.fillText('Email: ' + email, 104, top + 137);
                context.fillText('Password: ' + password, 104, top + 179);
            };

            drawAccount(
                190,
                'AKUN SISWA',
                credentials.student_name,
                credentials.student_email,
                credentials.student_password || credentials.password,
            );
            drawAccount(
                408,
                'AKUN ORANG TUA',
                credentials.parent_name,
                credentials.parent_email,
                credentials.parent_password || 'Gunakan password akun lama',
            );

            context.fillStyle = '#71717a';
            context.font = '400 20px Arial, sans-serif';
            context.fillText('Simpan dengan aman. Password dapat diubah melalui menu Keamanan Akun.', 72, 676);
            context.fillText('Dokumen dibuat pada ' + new Date().toLocaleString('id-ID'), 72, 716);

            const mime = isJpeg ? 'image/jpeg' : 'image/png';
            const extension = isJpeg ? 'jpg' : 'png';
            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'akun-buku-penghubung.' + extension;
                link.click();
                URL.revokeObjectURL(url);
            }, mime, 0.94);
        };
    </script>

    <div
        x-data="{ open: true, credentials: @js($credentials) }"
        x-show="open"
        x-cloak
        class="registration-credentials"
        role="dialog"
        aria-modal="true"
        aria-labelledby="registration-credentials-title"
        @keydown.escape.window="open = false"
    >
        <button type="button" class="registration-credentials__backdrop" aria-label="Tutup" @click="open = false"></button>

        <section class="registration-credentials__dialog">
            <header class="registration-credentials__header">
                <div class="registration-credentials__success-icon">
                    <x-filament::icon icon="gmdi-task-alt-o" />
                </div>
                <div>
                    <h2 id="registration-credentials-title">Registrasi Berhasil</h2>
                    <p>Simpan data login ini sekarang. Informasi password hanya ditampilkan satu kali.</p>
                </div>
                <button type="button" class="registration-credentials__close" title="Tutup" @click="open = false">
                    <x-filament::icon icon="gmdi-close" />
                    <span class="sr-only">Tutup</span>
                </button>
            </header>

            <div class="registration-credentials__accounts">
                <div class="registration-credentials__account">
                    <span>Akun Siswa</span>
                    <strong>{{ $credentials['student_name'] }}</strong>
                    <dl>
                        <dt>Email</dt>
                        <dd>{{ $credentials['student_email'] }}</dd>
                        <dt>Password</dt>
                        <dd>{{ $credentials['student_password'] ?? $credentials['password'] }}</dd>
                    </dl>
                </div>

                <div class="registration-credentials__account">
                    <span>Akun Orang Tua</span>
                    <strong>{{ $credentials['parent_name'] }}</strong>
                    <dl>
                        <dt>Email</dt>
                        <dd>{{ $credentials['parent_email'] }}</dd>
                        <dt>Password</dt>
                        <dd>
                            {{ $credentials['parent_password'] ?? null
                                ? $credentials['parent_password']
                                : 'Tetap menggunakan password akun lama' }}
                        </dd>
                    </dl>
                </div>
            </div>

            <footer class="registration-credentials__actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    icon="gmdi-image-o"
                    @click="downloadRegistrationCredentials(credentials, 'png')"
                >
                    Simpan PNG
                </x-filament::button>
                <x-filament::button
                    type="button"
                    color="gray"
                    icon="gmdi-photo-o"
                    @click="downloadRegistrationCredentials(credentials, 'jpeg')"
                >
                    Simpan JPG
                </x-filament::button>
                <x-filament::button type="button" icon="gmdi-check" @click="open = false">
                    Saya Sudah Menyimpan
                </x-filament::button>
            </footer>
        </section>
    </div>
@endif
