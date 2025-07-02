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
});
