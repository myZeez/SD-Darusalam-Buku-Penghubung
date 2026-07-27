<script>
    (() => {
        const storageKey = 'simpati-palette';
        const palettes = ['ceria', 'lembut', 'tenang'];

        const readPalette = () => {
            try {
                const savedPalette = window.localStorage.getItem(storageKey);

                return palettes.includes(savedPalette) ? savedPalette : 'tenang';
            } catch (error) {
                return 'tenang';
            }
        };

        const applyPalette = () => {
            document.documentElement.dataset.simpatiPalette = readPalette();
        };

        applyPalette();
        document.addEventListener('livewire:navigated', applyPalette);
        window.addEventListener('storage', applyPalette);
    })();
</script>
