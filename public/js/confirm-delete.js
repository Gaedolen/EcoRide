document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.form-delete');

  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      // Création de la modale si elle n'existe pas
      if (!document.querySelector('.modal-confirm')) {
        createModal();
      }

      const modal = document.querySelector('.modal-confirm');
      modal.style.display = 'block';

      // Boutons de la modale
      const btnConfirm = modal.querySelector('.modal-confirm-yes');
      const btnCancel = modal.querySelector('.modal-confirm-no');

      // Si on clique sur "Oui", on soumet le formulaire
      btnConfirm.onclick = () => {
        modal.style.display = 'none';
        form.submit();
      };

      // Si on clique sur "Non" ou en dehors, on ferme la modale
      btnCancel.onclick = () => {
        modal.style.display = 'none';
      };

      window.onclick = (e) => {
        if (e.target === modal) {
          modal.style.display = 'none';
        }
      };
    });
  });

  function createModal() {
    const modalHtml = `
      <div class="modal-confirm">
        <div class="modal-content">
          <p>Voulez-vous vraiment supprimer cet employé ? Cette action est irréversible.</p>
          <div class="modal-buttons">
            <button class="modal-confirm-yes">Oui</button>
            <button class="modal-confirm-no">Non</button>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
  }
});
