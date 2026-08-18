{{-- Applied before paint so the page never flashes the wrong theme. --}}
<script>
    (() => {
        const stored = localStorage.getItem('theme');
        const dark = stored === 'dark' || (! stored && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
