<x-filament-widgets::widget class="fi-wi-palette-switcher">
    <section
        class="palette-switcher"
        aria-labelledby="palette-switcher-heading"
        x-data="{
            palette: document.documentElement.dataset.simpatiPalette || 'tenang',
            choose(value) {
                this.palette = value;
                document.documentElement.dataset.simpatiPalette = value;

                try {
                    window.localStorage.setItem('simpati-palette', value);
                } catch (error) {}
            },
        }"
    >
        <div class="palette-switcher__copy">
            <span class="palette-switcher__icon" aria-hidden="true">
                <x-filament::icon icon="gmdi-palette-o" />
            </span>
            <div>
                <h2 id="palette-switcher-heading">Suasana dashboard</h2>
                <p>Pilih warna yang paling nyaman. Pilihan tersimpan otomatis di perangkat ini.</p>
            </div>
        </div>

        <div class="palette-switcher__options" role="group" aria-label="Pilihan palet warna">
            <button
                type="button"
                class="palette-option palette-option--ceria"
                :class="{ 'is-active': palette === 'ceria' }"
                :aria-pressed="palette === 'ceria'"
                @click="choose('ceria')"
            >
                <span class="palette-option__swatches" aria-hidden="true">
                    <i></i><i></i><i></i><i></i>
                </span>
                <span><strong>Ceria</strong><small>Penuh warna</small></span>
                <x-filament::icon icon="gmdi-check-circle-o" class="palette-option__check" />
            </button>

            <button
                type="button"
                class="palette-option palette-option--lembut"
                :class="{ 'is-active': palette === 'lembut' }"
                :aria-pressed="palette === 'lembut'"
                @click="choose('lembut')"
            >
                <span class="palette-option__swatches" aria-hidden="true">
                    <i></i><i></i><i></i><i></i>
                </span>
                <span><strong>Ungu Lembut</strong><small>Hangat dan ramah</small></span>
                <x-filament::icon icon="gmdi-check-circle-o" class="palette-option__check" />
            </button>

            <button
                type="button"
                class="palette-option palette-option--tenang"
                :class="{ 'is-active': palette === 'tenang' }"
                :aria-pressed="palette === 'tenang'"
                @click="choose('tenang')"
            >
                <span class="palette-option__swatches" aria-hidden="true">
                    <i></i><i></i><i></i><i></i>
                </span>
                <span><strong>Tenang</strong><small>Netral dan nyaman</small></span>
                <x-filament::icon icon="gmdi-check-circle-o" class="palette-option__check" />
            </button>
        </div>
    </section>
</x-filament-widgets::widget>
