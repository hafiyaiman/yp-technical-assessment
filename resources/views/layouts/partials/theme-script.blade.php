<script>
    (() => {
        const storage = 'dark-theme';
        const root = document.documentElement;
        const query = '(prefers-color-scheme: dark)';

        const isDark = () => {
            const mode = localStorage.getItem(storage) ?? 'light';

            return mode === 'dark'
                || mode === 'true'
                || (mode === 'system' && window.matchMedia(query).matches);
        };

        const apply = () => {
            const dark = isDark();

            root.classList.toggle('dark', dark);
            root.style.colorScheme = dark ? 'dark' : 'light';
        };

        apply();

        document.addEventListener('livewire:navigated', apply);
        window.addEventListener('storage', (event) => {
            if (event.key === storage) {
                apply();
            }
        });
        window.matchMedia(query).addEventListener('change', apply);
    })();
</script>
