document.addEventListener('DOMContentLoaded', function () {
    const chartCanvas = document.getElementById('salesChart');
    if (!chartCanvas || !window.dashboardData) return;

    const ctx = chartCanvas.getContext('2d');

    // Khởi tạo Biểu đồ Cột Doanh Thu bằng Chart.js
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: window.dashboardData.labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: window.dashboardData.sales,
                backgroundColor: '#16834b',
                hoverBackgroundColor: '#1e3a29',
                borderRadius: 6,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let value = context.raw || 0;
                            return ' Doanh thu: ' + value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { weight: '600' } }
                },
                y: {
                    border: { dash: [4, 4] },
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#64748b',
                        callback: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + ' Tr';
                            }
                            return value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            }
        }
    });
});