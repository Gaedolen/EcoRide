document.addEventListener('DOMContentLoaded', () => {
    const reportPopup = document.getElementById('report-popup');
    const reportClose = document.getElementById('close-report-popup');
    const reportForm = document.getElementById('report-form');

    // Ouverture du popup
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('open-report-popup')) {
            e.preventDefault();
            const button = e.target;
            document.getElementById('reportedUserId').value = button.dataset.reportedUserId;
            document.getElementById('covoiturageId').value = button.dataset.covoiturageId;
            reportPopup.style.display = 'flex';
        }
    });

    // Fermeture du popup
    reportClose.addEventListener('click', () => { reportPopup.style.display = 'none'; });
    window.addEventListener('click', e => {
        if (e.target === reportPopup) reportPopup.style.display = 'none';
    });

    // Soumission du formulaire
    reportForm.addEventListener('submit', e => {
        e.preventDefault();
        e.stopPropagation(); // Empêche toute propagation

        const reportedUserId = document.getElementById('reportedUserId').value;
        const covoiturageId = document.getElementById('covoiturageId').value;
        const message = document.getElementById('message').value;
        const token = document.getElementById('report-token').value;

        if (!message) return alert('Veuillez saisir un message.');

        fetch(reportForm.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                reported_user_id: reportedUserId,
                covoiturage_id: covoiturageId,
                message: message,
                _token: token
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Fermer le popup et réinitialiser le formulaire
                reportPopup.style.display = 'none';
                reportForm.reset();

                // Mettre à jour le bouton en badge "Signalement en attente"
                const button = document.querySelector(`.open-report-popup[data-reported-user-id="${reportedUserId}"][data-covoiturage-id="${covoiturageId}"]`);
                if (button) {
                    const badge = document.createElement('span');
                    badge.className = 'badge badge-warning';
                    badge.textContent = '⏳ Signalement en attente';
                    button.replaceWith(badge);
                }
            } else {
                alert(data.message || 'Erreur lors de l\'envoi du signalement.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur réseau lors de l\'envoi du signalement.');
        });
    });
});
