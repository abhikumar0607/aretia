(function () {
    function openCasesUrl(url) {
        if (url) {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    }

    document.querySelectorAll('.dashboard-chart-breakdown-item--linked[data-cases-url]').forEach(function (row) {
        row.addEventListener('click', function () {
            openCasesUrl(row.getAttribute('data-cases-url'));
        });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCasesUrl(row.getAttribute('data-cases-url'));
            }
        });
    });

    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#64748b';

    function hexToRgba(hex, alpha) {
        var h = hex.replace('#', '');
        if (h.length === 3) {
            h = h.split('').map(function (c) { return c + c; }).join('');
        }
        var n = parseInt(h, 16);
        return 'rgba(' + [(n >> 16) & 255, (n >> 8) & 255, n & 255].join(',') + ',' + alpha + ')';
    }

    document.querySelectorAll('.dashboard-chart-canvas-wrap canvas[data-values]').forEach(function (canvas) {
        var labels = JSON.parse(canvas.dataset.labels || '[]');
        var values = JSON.parse(canvas.dataset.values || '[]');
        var colors = JSON.parse(canvas.dataset.colors || '[]');
        var chartType = canvas.dataset.chartType || 'doughnut';
        var variant = canvas.dataset.chartVariant || 'default';
        var layout = canvas.dataset.chartLayout || 'ring';
        var isHorizontal = canvas.dataset.chartHorizontal === '1' || layout === 'bars-h';

        if (!values.length) return;

        var dataset = {
            data: values,
            backgroundColor: colors,
            borderColor: '#ffffff',
            borderWidth: chartType === 'bar' ? 0 : 2,
            hoverOffset: chartType === 'doughnut' || chartType === 'pie' ? 8 : 0,
            borderRadius: chartType === 'bar' ? 10 : 0,
            borderSkipped: false,
            maxBarThickness: chartType === 'bar' ? 44 : undefined,
        };

        if (chartType === 'bar') {
            dataset.backgroundColor = function (context) {
                var chart = context.chart;
                var idx = context.dataIndex;
                var color = colors[idx] || '#94a3b8';
                if (!chart.chartArea) return color;
                if (isHorizontal) {
                    var g = chart.ctx.createLinearGradient(chart.chartArea.left, 0, chart.chartArea.right, 0);
                    g.addColorStop(0, hexToRgba(color, 0.2));
                    g.addColorStop(1, color);
                    return g;
                }
                var g2 = chart.ctx.createLinearGradient(0, chart.chartArea.bottom, 0, chart.chartArea.top);
                g2.addColorStop(0, hexToRgba(color, 0.25));
                g2.addColorStop(1, color);
                return g2;
            };
            dataset.borderColor = 'transparent';
        }

        if (chartType === 'polarArea') {
            dataset.backgroundColor = colors.map(function (c) { return hexToRgba(c, 0.75); });
            dataset.borderColor = colors;
            dataset.borderWidth = 2;
        }

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    cornerRadius: 10,
                    titleFont: { weight: '600' },
                    callbacks: {
                        label: function (ctx) {
                            var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                            var pct = total ? Math.round((ctx.raw / total) * 100) : 0;
                            return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                        },
                    },
                },
            },
        };

        if (chartType === 'doughnut') {
            options.cutout = variant === 'status' ? '62%' : '72%';
            dataset.borderWidth = 3;
            dataset.hoverBorderColor = '#ffffff';
        }

        if (chartType === 'pie') {
            options.cutout = 0;
            dataset.borderWidth = 2;
            dataset.hoverBorderColor = '#ffffff';
        }

        if (chartType === 'polarArea') {
            options.scales = {
                r: {
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { display: false },
                    pointLabels: { display: false },
                },
            };
        }

        var stageLinks = [];
        if (canvas.dataset.stageLinks) {
            try {
                stageLinks = JSON.parse(canvas.dataset.stageLinks || '[]');
            } catch (e) {
                stageLinks = [];
            }
        }

        if (stageLinks.length) {
            options.onHover = function (event, elements) {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            };
            options.onClick = function (event, elements) {
                if (!elements.length) return;
                var url = stageLinks[elements[0].index];
                if (url) {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            };
        }

        if (chartType === 'bar') {
            options.indexAxis = isHorizontal ? 'y' : 'x';
            options.layout = { padding: { left: 4, right: 12, top: 8, bottom: 8 } };
            options.datasets = {
                bar: {
                    barPercentage: 0.72,
                    categoryPercentage: 0.82,
                },
            };
            options.scales = {
                x: {
                    display: true,
                    beginAtZero: true,
                    grid: {
                        display: isHorizontal,
                        color: 'rgba(148, 163, 184, 0.12)',
                    },
                    ticks: {
                        font: { size: 11 },
                        precision: 0,
                        display: isHorizontal,
                    },
                },
                y: {
                    display: true,
                    grid: { display: !isHorizontal, color: 'rgba(148, 163, 184, 0.12)' },
                    ticks: {
                        font: { size: 11 },
                        maxRotation: 0,
                        autoSkip: false,
                        display: isHorizontal,
                    },
                },
            };
            if (!isHorizontal) {
                options.scales.x.ticks.display = true;
                options.scales.y.ticks.display = true;
            }
        }

        new Chart(canvas, {
            type: chartType,
            data: { labels: labels, datasets: [dataset] },
            options: options,
        });
    });
})();
