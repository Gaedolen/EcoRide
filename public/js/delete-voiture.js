document.addEventListener('DOMContentLoaded', () => {

    /* Supprimer une voiture */
    const deleteButtons = document.querySelectorAll('.btn-supprimer-voiture');
    const modal = document.getElementById('confirmation-modal');
    const confirmYes = document.getElementById('confirm-yes');
    const confirmNo = document.getElementById('confirm-no');
    let currentForm = null;

    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            currentForm = button.closest('form');
            modal.classList.remove('hidden');
        });
    });

    confirmYes.addEventListener('click', () => {
        if (currentForm) {
            currentForm.submit();
        }
        modal.classList.add('hidden');
        currentForm = null;
    });

    confirmNo.addEventListener('click', () => {
        modal.classList.add('hidden');
        currentForm = null;
    });

    /* Supprimer un covoiturage */
    (function() {
        const buttons = document.querySelectorAll('button.btn-supprimer-covoiturage');
        const modal = document.getElementById('confirmation-modal-covoiturage');
        const confirmYes = document.getElementById('confirm-yes-covoiturage');
        const confirmNo = document.getElementById('confirm-no-covoiturage');
        let currentForm = null;

        if (!modal || !confirmYes || !confirmNo) return;

        buttons.forEach(button => {
            button.addEventListener('click', e => {
                e.preventDefault();
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
    })();


    /* Annulation d'une réservation */
    (function() {
        const modal = document.getElementById('cancel-reservation-modal');
        const form = document.getElementById('cancel-reservation-form');
        const tokenInput = document.getElementById('cancel-token');

        if (!modal || !form || !tokenInput) return;

        document.querySelectorAll('.open-cancel-popup').forEach(btn => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.actionUrl;
                tokenInput.value = btn.dataset.token;
                modal.classList.remove('hidden');
            });
        });

        modal.querySelectorAll('.btn-confirm-no').forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
        });
    })();


    /* Laisser un avis */
    (function() {
        const avisButtons = document.querySelectorAll('.open-avis-popup');
        const popup = document.getElementById('avis-popup');
        if (!popup) return;
        const close = popup.querySelector('.close-popup');
        const cibleInput = document.getElementById('cible_id');
        const covoiturageInput = document.getElementById('covoiturage_id');

        avisButtons.forEach(button => {
            button.addEventListener('click', e => {
                e.preventDefault();
                cibleInput.value = button.dataset.cibleId;
                covoiturageInput.value = button.dataset.avisCovoiturageId;
                popup.style.display = 'flex';
            });
        });

        if (close) close.addEventListener('click', () => popup.style.display = 'none');

        window.addEventListener('click', e => {
            if (e.target === popup) popup.style.display = 'none';
        });
    })();


    /* Signalement */
    (function() {
        const popup = document.getElementById('report-popup');
        const close = document.getElementById('close-report-popup');
        const form = document.getElementById('report-form');
        if (!popup || !form) return;

        const reportedUserInput = document.getElementById('reportedUserId');
        const covoiturageInput = document.getElementById('covoiturageId');
        const tokenInput = document.getElementById('report-token');

        document.querySelectorAll('.open-report-popup').forEach(btn => {
            btn.addEventListener('click', () => {
                reportedUserInput.value = btn.dataset.reportedUserId;
                covoiturageInput.value = btn.dataset.covoiturageId;
                popup.style.display = 'flex';
            });
        });

        if (close) close.addEventListener('click', () => popup.style.display = 'none');
        popup.addEventListener('click', e => { if (e.target === popup) popup.style.display = 'none'; });

        form.addEventListener('submit', e => {
            e.preventDefault();
            fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    _token: tokenInput.value,
                    reported_user_id: reportedUserInput.value,
                    covoiturage_id: covoiturageInput.value,
                    message: document.getElementById('message').value
                })
            })
            .then(res => res.json())
            .then(() => {
                popup.style.display = 'none';
                form.reset();
            });
        });
    })();

});
