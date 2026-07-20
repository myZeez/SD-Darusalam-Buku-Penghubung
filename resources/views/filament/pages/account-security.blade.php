<x-filament-panels::page>
    @if (auth()->user()->must_change_password)
        <div class="account-security-alert" role="alert">
            <x-filament::icon icon="gmdi-warning-amber-o" />
            <div>
                <strong>Password sementara masih aktif</strong>
                <p>Ganti password sekarang agar menu aplikasi lainnya dapat digunakan.</p>
            </div>
        </div>
    @endif

    <form wire:submit="changePassword" class="account-security-form">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="gmdi-password-o" wire:loading.attr="disabled">
                Simpan Password Baru
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
