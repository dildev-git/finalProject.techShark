// Tab switching function
function openManagerTab(tabId, btn) {
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.style.display = 'none');

    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(b => b.classList.remove('active'));

    document.getElementById(tabId).style.display = 'block';
    btn.classList.add('active');
}

// Chart.js chart creation
document.addEventListener('DOMContentLoaded', function () {
    // Check if window.chartData exists, sent from PHP
    if (window.chartData) {

        // 1. Sales Line Chart
        const lineCtx = document.getElementById('salesLineChart');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: window.chartData.lineLabels,
                    datasets: [
                        {
                            label: 'Revenue (LKR)',
                            data: window.chartData.lineRevenue,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.08)',
                            tension: 0.45,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#6366f1',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: window.chartData.lineOrders,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.06)',
                            tension: 0.45,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#f59e0b',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.yAxisID === 'y'
                                    ? ' LKR ' + ctx.parsed.y.toLocaleString()
                                    : ' ' + ctx.parsed.y + ' orders'
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear', display: true, position: 'left',
                            ticks: { callback: v => 'LKR ' + v.toLocaleString() }
                        },
                        y1: {
                            type: 'linear', display: true, position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        }

        // 2. Category Pie Chart
        const pieCtx = document.getElementById('categoryPieChart');
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: window.chartData.pieLabels,
                    datasets: [{
                        data: window.chartData.pieData,
                        backgroundColor: window.chartData.pieColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' LKR ' + ctx.parsed.toLocaleString()
                            }
                        }
                    }
                }
            });
        }
    }
});