<script>
    try {
        const root = document.documentElement;
        root.dataset.theme = localStorage.getItem('majra-theme') === 'light' ? 'light' : 'dark';
        root.dataset.weatherMotion = localStorage.getItem('majra-weather-motion') === 'paused' ? 'paused' : 'playing';
    } catch {}
</script>
