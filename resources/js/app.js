import Chart from 'chart.js/auto';

Chart.defaults.font.family = 'Nunito, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.font.weight = 600;
Chart.defaults.animation.duration = 400;

/**
 * Read a theme token so charts follow the light and dark palettes.
 */
const token = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('lineChart', (labels, datasets) => ({
        chart: null,
        observer: null,

        init() {
            this.render();

            // Redraw when the theme class flips so the axes stay readable.
            this.observer = new MutationObserver(() => this.render());
            this.observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
        },

        destroy() {
            this.observer?.disconnect();
            this.chart?.destroy();
        },

        render() {
            this.chart?.destroy();

            const accent = token('--ui-accent');
            const muted = token('--ui-ink-muted');
            const line = token('--ui-line');

            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: datasets.map((dataset, index) => ({
                        ...dataset,
                        borderColor: index === 0 ? accent : muted,
                        backgroundColor: index === 0 ? accent + '1f' : 'transparent',
                        pointBackgroundColor: index === 0 ? accent : muted,
                        pointRadius: labels.length > 12 ? 0 : 3,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                        fill: index === 0,
                        tension: 0.35,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: datasets.length > 1,
                            labels: { color: muted, boxWidth: 8, boxHeight: 8, usePointStyle: true },
                        },
                        tooltip: { displayColors: false, padding: 10, cornerRadius: 8 },
                    },
                    scales: {
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { color: muted, maxRotation: 0, autoSkipPadding: 16 },
                        },
                        y: {
                            border: { display: false },
                            grid: { color: line },
                            ticks: { color: muted, maxTicksLimit: 5, padding: 8 },
                        },
                    },
                },
            });
        },
    }));
});
