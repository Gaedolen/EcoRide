document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.form-delete');

  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      // Si déjà confirmé, on laisse passer
      if (form.classList.contains('confirmed')) return;

      event.preventDefault();

      // Création de la modale si elle n'existe pas
      if (!document.querySelector('.modal-confirm')) {
        createModal();
      }

      const modal = document.querySelector('.modal-confirm');
      modal.style.display = 'block';

      const btnConfirm = modal.querySelector('.modal-confirm-yes');
      const btnCancel = modal.querySelector('.modal-confirm-no');

      // Nettoie les anciens handlers pour éviter doublons
      btnConfirm.onclick = null;
      btnCancel.onclick = null;

      // Confirmation
      btnConfirm.onclick = () => {
        modal.style.display = 'none';
        form.classList.add('confirmed');

        // Création d’un bouton submit invisible
        const realSubmit = document.createElement('button');
        realSubmit.type = 'submit';
        realSubmit.style.display = 'none';
        form.appendChild(realSubmit);

        // On déclenche un clic sur ce bouton
        realSubmit.click();
      };

      // Annulation
      btnCancel.onclick = () => {
        modal.style.display = 'none';
      };

      // Clic hors de la modale ferme la modale
      window.onclick = (e) => {
        if (e.target === modal) {
          modal.style.display = 'none';
        }
      };
    });
  });
})