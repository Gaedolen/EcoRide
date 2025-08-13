document.addEventListener('DOMContentLoaded', () => {
    function setupModal(buttonSelector, modalId, yesId, noId) {
        const buttons = document.querySelectorAll(buttonSelector);
        const modal = document.getElementById(modalId);
        const confirmYes = document.getElementById(yesId);
        const confirmNo = document.getElementById(noId);
        let currentForm = null;

        buttons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                currentForm = button.closest('form');
                modal.classList.remove('hidden');
            });
        });

        confirmYes.addEventListener('click', () => {
            if (currentForm) currentForm.submit();
            modal.classList.add('hidden');
            currentForm = null;
        });

        confirmNo.addEventListener('click', () => {
            modal.classList.add('hidden');
            currentForm = null;
        });
    }

    // Supprimer une voiture
    setupModal('button[data-voiture-id]', 'confirmation-modal', 'confirm-yes', 'confirm-no');

    // Supprimer un covoiturage
    setupModal('.delete-form button[data-covoiturage-id]', 'confirmation-modal-covoiturage', 'confirm-yes-covoiturage', 'confirm-no-covoiturage');

    // Annulation d'une réservation
    const cancelButtons = document.querySelectorAll('.open-cancel-popup');
    const cancelModal = document.getElementById('cancel-reservation-modal');
    const cancelForm = document.getElementById('cancel-reservation-form');
    const cancelNo = cancelModal.querySelector('.btn-confirm-no');

    cancelButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const actionUrl = this.getAttribute('data-action-url');
            cancelForm.setAttribute('action', actionUrl);
            cancelModal.classList.remove('hidden');
        });
    });

    cancelNo.addEventListener('click', () => {
        cancelModal.classList.add('hidden');
    });

    // Laisser un avis
    const avisButtons = document.querySelectorAll('.open-avis-popup');
    const avisPopup = document.getElementById('avis-popup');
    const avisClose = avisPopup.querySelector('.close-popup');
    const cibleIdInput = document.getElementById('cible_id');
    const covoiturageIdInput = document.getElementById('covoiturage_id');

    avisButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            cibleIdInput.value = button.dataset.cibleId;
            covoiturageIdInput.value = button.dataset.avisCovoiturageId;
            avisPopup.style.display = 'flex';
        });
    });

    avisClose.addEventListener('click', () => {
        avisPopup.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === avisPopup) {
            avisPopup.style.display = 'none';
        }
    });

    // Faire un signalement
    const reportPopup = document.getElementById('report-popup');
    const reportClose = document.getElementById('close-report-popup');
    const reportForm = document.getElementById('report-form');
    const reportButtons = document.querySelectorAll('.open-report-popup');

    // Ouvre le popup et remplit les hidden fields
    reportButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('reportedUserId').value = btn.dataset.reportedUserId;
            document.getElementById('covoiturageId').value = btn.dataset.covoiturageId;
            reportPopup.style.display = 'flex';
        });
    });

    // Ferme le popup
    reportClose.addEventListener('click', () => reportPopup.style.display = 'none');
    reportPopup.addEventListener('click', e => { if (e.target === reportPopup) reportPopup.style.display = 'none'; });

    // Submit via fetch
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
            // On ferme juste la popup
            reportPopup.style.display = 'none';
            reportForm.reset();
        });
    });
});
