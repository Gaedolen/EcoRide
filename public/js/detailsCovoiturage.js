document.addEventListener('DOMContentLoaded', () => {
    const reserveButtons = document.querySelectorAll('.open-reservation-popup');
    const reservationModal = document.getElementById('reservation-modal');
    const reservationMessage = document.getElementById('reservation-message');
    const btnYes = reservationModal.querySelector('.btn-confirm-yes');
    const btnNo = reservationModal.querySelector('.btn-confirm-no');

    reserveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const credits = parseInt(this.getAttribute('data-credits'));
            const newCredits = credits - 2;
            reservationMessage.textContent = `Votre crédit sera de ${newCredits}. Voulez-vous covoiturer ?`;

            reservationModal.classList.remove('hidden');

            // Oui → soumettre le formulaire
            btnYes.onclick = () => {
                reservationModal.classList.add('hidden');
                this.closest('form').submit();
            };

            // Non → fermer le modal
            btnNo.onclick = () => {
                reservationModal.classList.add('hidden');
            };
        });
    });
});
