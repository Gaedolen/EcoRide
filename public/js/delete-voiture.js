document.addEventListener('DOMContentLoaded', () => {
    // Supprimer une voiture
    const voitureButtons = document.querySelectorAll('button[data-voiture-id]');
    const voitureModal = document.getElementById('confirmation-modal');
    const voitureConfirmYes = document.getElementById('confirm-yes');
    const voitureConfirmNo = document.getElementById('confirm-no');
    let currentVoitureForm = null;

    voitureButtons.forEach(button => {
        button.addEventListener('click', () => {
            currentVoitureForm = button.closest('form');
            voitureModal.classList.remove('hidden');
        });
    });

    voitureConfirmYes.addEventListener('click', () => {
        if (currentVoitureForm) currentVoitureForm.submit();
        voitureModal.classList.add('hidden');
    });

    voitureConfirmNo.addEventListener('click', () => {
        voitureModal.classList.add('hidden');
        currentVoitureForm = null;
    });

    // Supprimer un covoiturage
    const covoiturageButtons = document.querySelectorAll('button[data-covoiturage-id]');
    const covoiturageModal = document.getElementById('confirmation-modal-covoiturage');
    const confirmYesCovoiturage = document.getElementById('confirm-yes-covoiturage');
    const confirmNoCovoiturage = document.getElementById('confirm-no-covoiturage');
    let currentCovoiturageForm = null;

    covoiturageButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            currentCovoiturageForm = button.closest('form');
            covoiturageModal.classList.remove('hidden');
        });
    });

    confirmYesCovoiturage.addEventListener('click', () => {
        if (currentCovoiturageForm) {
            currentCovoiturageForm.submit();
            currentCovoiturageForm = null;
        }
        covoiturageModal.classList.add('hidden');
    });

    confirmNoCovoiturage.addEventListener('click', () => {
        covoiturageModal.classList.add('hidden');
        currentCovoiturageForm = null;
    });

    // Annulation d'une réservation
    const cancelButtons = document.querySelectorAll('.open-cancel-popup');
    const modal = document.getElementById('cancel-reservation-modal');
    const form = document.getElementById('cancel-reservation-form');
    const cancelNo = modal.querySelector('.btn-confirm-no');

    cancelButtons.forEach(button => {
        button.addEventListener('click', function () {
            const actionUrl = this.getAttribute('data-action-url');
            form.setAttribute('action', actionUrl);
            modal.classList.remove('hidden');
        });
    });

    cancelNo.addEventListener('click', () => {
        modal.classList.add('hidden');
    });


    // Laisser un avis après un trajet
    const avisButtons = document.querySelectorAll('.open-avis-popup');
    const avisPopup = document.getElementById('avis-popup');
    const avisClose = document.querySelector('.close-popup');
    const cibleIdInput = document.getElementById('cible_id');

    avisButtons.forEach(button => {
        button.addEventListener('click', () => {
            const cibleId = button.getAttribute('data-cible-id');
            cibleIdInput.value = cibleId;
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
});
