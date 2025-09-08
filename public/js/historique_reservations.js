document.addEventListener('DOMContentLoaded', () => {
    const avisPopup = document.getElementById('avis-popup');
    const avisClose = avisPopup.querySelector('.close-popup');
    const cibleIdInput = document.getElementById('cible_id');
    const covoiturageIdInput = document.getElementById('covoiturage_id');

    const reportPopup = document.getElementById('report-popup');
    const reportClose = document.getElementById('close-report-popup');
    const reportForm = document.getElementById('report-form');

    document.addEventListener('click', (e) => {
        // Laisser un avis
        if (e.target.classList.contains('open-avis-popup')) {
            e.preventDefault();
            const button = e.target;
            cibleIdInput.value = button.dataset.cibleId;
            covoiturageIdInput.value = button.dataset.avisCovoiturageId;
            avisPopup.style.display = 'flex';
        }

        // Signaler
        if (e.target.classList.contains('open-report-popup')) {
            e.preventDefault();
            const button = e.target;
            document.getElementById('reportedUserId').value = button.dataset.reportedUserId;
            document.getElementById('covoiturageId').value = button.dataset.covoiturageId;
            reportPopup.style.display = 'flex';
        }
    });

    // Fermeture des popups
    avisClose.addEventListener('click', () => { avisPopup.style.display = 'none'; });
    reportClose.addEventListener('click', () => { reportPopup.style.display = 'none'; });

    window.addEventListener('click', e => {
        if (e.target === avisPopup) avisPopup.style.display = 'none';
        if (e.target === reportPopup) reportPopup.style.display = 'none';
    });

    // Soumission du report via fetch
    reportForm.addEventListener('submit', e => {
        e.preventDefault();
        fetch(reportForm.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                reported_user_id: document.getElementById('reportedUserId').value,
                covoiturage_id: document.getElementById('covoiturageId').value,
                message: document.getElementById('message').value
            })
        })
        .then(res => res.json())
        .then(data => {
            reportPopup.style.display = 'none';
            reportForm.reset();
        });
    });
});
