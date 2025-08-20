document.addEventListener("DOMContentLoaded", () => {
    const { covoiturages, credits } = window.dashboardData;

    // Formater les dates en jj/mm/aaaa
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString("fr-FR");
    }

    const covoituragesChart = new Chart(
        document.getElementById('covoituragesChart'),
        {
            type: 'bar',
            data: {
                labels: covoiturages.labels.map(formatDate),
                datasets: [{
                    label: "Covoiturages",
                    data: covoiturages.values,
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
                labels: credits.labels.map(formatDate),
                datasets: [{
                    label: "Crédits",
                    data: credits.values,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
                    borderColor: "rgba(75, 192, 192, 1)",
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
});
