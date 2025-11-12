document.addEventListener('DOMContentLoaded', () => {

    // --- Chart Initialization ---

    // 1. Pacing Report Chart
    const pacingCtx = document.getElementById('pacingChart')?.getContext('2d');
    if (pacingCtx) {
        new Chart(pacingCtx, {
            type: 'bar',
            data: {
                labels: reportData.pacing.labels,
                datasets: [{
                    label: 'This Year Bookings',
                    data: reportData.pacing.thisYear,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Last Year Bookings',
                    data: reportData.pacing.lastYear,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Monthly Reservation Comparison' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 2. Source of Business Chart
    const sourceCtx = document.getElementById('sourceChart')?.getContext('2d');
    if (sourceCtx) {
        new Chart(sourceCtx, {
            type: 'pie',
            data: {
                labels: reportData.source.labels,
                datasets: [{
                    label: 'Reservations by Source',
                    data: reportData.source.counts,
                    backgroundColor: [
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                    ],
                    borderColor: [
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Reservation Source Distribution' }
                }
            }
        });
    }

    // 3. Guest Demographics Chart
    const demographicsCtx = document.getElementById('demographicsChart')?.getContext('2d');
    if (demographicsCtx) {
        new Chart(demographicsCtx, {
            type: 'doughnut',
            data: {
                labels: ['New Guests', 'Returning Guests'],
                datasets: [{
                    label: 'Guest Type',
                    data: [reportData.demographics.newGuests, reportData.demographics.returningGuests],
                     backgroundColor: [
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(54, 162, 235, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 206, 86, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
             options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'New vs. Returning Guests' }
                }
            }
        });
    }

    // NOTE FOR DEVELOPER: To make the date filters work, you would add an event listener
    // to the 'Apply' button. It would fetch new data from a PHP script using the date range
    // and then use chart.data = newData; chart.update(); to refresh the charts.

    // --- Export and Print Functionality ---

    document.querySelectorAll('.export-csv').forEach(button => {
        button.addEventListener('click', () => {
            const chartId = button.dataset.target;
            const chart = Chart.getChart(chartId);
            if (chart) {
                exportChartDataToCSV(chart, chartId + '_data.csv');
            }
        });
    });

    document.querySelectorAll('.print-chart').forEach(button => {
        button.addEventListener('click', () => {
            const chartId = button.dataset.target;
            const canvas = document.getElementById(chartId);
            if (canvas) {
                const dataUrl = canvas.toDataURL('image/png');
                const windowContent = `<!DOCTYPE html><html><head><title>Print Chart</title></head><body><img src="${dataUrl}" style="max-width: 100%;"></body></html>`;
                const printWin = window.open('', '', 'width=800,height=600');
                printWin.document.open();
                printWin.document.write(windowContent);
                printWin.document.close();
                printWin.focus();
                printWin.print();
                printWin.close();
            }
        });
    });

    function exportChartDataToCSV(chart, filename) {
        const { labels, datasets } = chart.data;
        let csvContent = "data:text/csv;charset=utf-8,";

        // Header Row
        const header = ['Category', ...datasets.map(d => d.label)].join(',');
        csvContent += header + "\r\n";

        // Data Rows
        labels.forEach((label, index) => {
            const row = [label, ...datasets.map(d => d.data[index])].join(',');
            csvContent += row + "\r\n";
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});