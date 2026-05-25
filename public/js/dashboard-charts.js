(function () {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#64748b';

    document.querySelectorAll('.dashboard-chart-canvas-wrap canvas[data-values]').forEach(function (canvas) {
        var labels = JSON.parse(canvas.dataset.labels || '[]');
        var values = JSON.parse(canvas.dataset.values || '[]');
        var colors = JSON.parse(canvas.dataset.colors || '[]');

        if (!values.length) return;

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverBorderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total ? Math.round((ctx.raw / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
    });
})();
