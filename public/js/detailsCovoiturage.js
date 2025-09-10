document.addEventListener('DOMContentLoaded', () => {

    const reservationModal = document.getElementById('reservation-modal');
    const reservationMessage = document.getElementById('reservation-message');
    const btnYes = reservationModal?.querySelector('.btn-confirm-yes');
    const btnNo = reservationModal?.querySelector('.btn-confirm-no');

    document.querySelectorAll('.btn-reserver').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = btn.closest('form');
            const credits = parseInt(btn.dataset.credits || 0);
            const newCredits = credits - 2;

            if (credits < 2) {
                alert('Vous n’avez pas assez de crédits pour réserver.');
                return;
            }

            reservationMessage.textContent = `Confirmez-vous la réservation pour ce covoiturage ? Votre crédit sera de ${newCredits}.`;
            reservationModal.classList.remove('hidden');

            btnYes.onclick = () => form.submit();
            btnNo.onclick = () => reservationModal.classList.add('hidden');
        });
    });

});
