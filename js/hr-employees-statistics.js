/**
 * HR Employees index — Chart.js statistics (status / department / monthly).
 * Config: window._hrEmployeesStatsConfig
 */
(function () {
    'use strict';

    var config = window._hrEmployeesStatsConfig || null;
    if (!config || typeof Chart === 'undefined') {
        return;
    }

    var lang = config.lang || {};
    var palette = [
        'rgb(54, 162, 235)',
        'rgb(75, 192, 192)',
        'rgb(255, 206, 86)',
        'rgb(255, 99, 132)',
        'rgb(153, 102, 255)',
        'rgb(255, 159, 64)',
        'rgb(201, 203, 207)',
        'rgb(100, 181, 246)',
        'rgb(129, 199, 132)',
        'rgb(239, 154, 154)',
    ];

    function colorsFor(count) {
        var colors = [];
        var i;
        for (i = 0; i < count; i++) {
            colors.push(palette[i % palette.length]);
        }
        return colors;
    }

    function renderStatusChart() {
        var el = document.getElementById('hrEmpStatusChart');
        var data = config.byStatus || {};
        if (!el || !data.labels || !data.labels.length) {
            return;
        }

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values || [],
                    backgroundColor: colorsFor(data.labels.length),
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    }

    function renderDeptChart() {
        var el = document.getElementById('hrEmpDeptChart');
        var data = config.byDepartment || {};
        if (!el || !data.labels || !data.labels.length) {
            return;
        }

        new Chart(el, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: lang.count || '',
                    data: data.values || [],
                    backgroundColor: 'rgba(54, 162, 235, 0.65)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 10,
                        },
                    },
                },
            },
        });
    }

    function renderMonthlyChart() {
        var el = document.getElementById('hrEmpMonthlyChart');
        var data = config.monthly || {};
        if (!el || !data.labels || !data.labels.length) {
            return;
        }

        new Chart(el, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: lang.hires || '',
                        data: data.hires || [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.25,
                        fill: true,
                    },
                    {
                        label: lang.exits || '',
                        data: data.exits || [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.15)',
                        tension: 0.25,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }

    function init() {
        renderStatusChart();
        renderDeptChart();
        renderMonthlyChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
