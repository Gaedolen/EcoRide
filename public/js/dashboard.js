function parseDate(dateString) {
    const [year, month, day] = dateString.split('-');
    return new Date(year, month - 1, day); // JS months = 0-11
}

document.addEventListener("DOMContentLoaded", () => {
    const { covoiturages, credits } = window.dashboardData;

    const covoituragesChart = new Chart(
        document.getElementById('covoituragesChart'),
        {
            type: 'bar',
            data: {
                labels: covoiturages.labels.map(dateStr => parseDate(dateStr).toLocaleDateString("fr-FR")),
                datasets: [{
                    label: "Covoiturages",
                    data: covoiturages.values.map(Number), // <-- ici
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1
                }]
            },
            options: { 
                responsive: true, 
                plugins: { legend: { display: false } }, 
                scales: { 
                    x: { ticks: { maxRotation: 45, minRotation: 45 } }, 
                    y: { beginAtZero: true } 
                } 
            }
        }
    );

    const creditsChart = new Chart(
        document.getElementById('creditsChart'),
        {
            type: 'bar',
            data: {
                labels: credits.labels.map(dateStr => parseDate(dateStr).toLocaleDateString("fr-FR")),
                datasets: [{
                    label: "Crédits",
                    data: credits.values,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
                    borderColor: "rgba(75, 192, 192, 1)",
                    borderWidth: 1
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { maxRotation: 45, minRotation: 45 } }, y: { beginAtZero: true } } }
        }
    );
});
