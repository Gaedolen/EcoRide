document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.form-delete');

  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      if (form.classList.contains('confirmed')) return;

      event.preventDefault();

      const modal = document.querySelector('.modal-confirm');
      modal.style.display = 'block';

      const btnConfirm = modal.querySelector('.modal-confirm-yes');
      const btnCancel = modal.querySelector('.modal-confirm-no');

      // Nettoyage pour éviter doublons
      btnConfirm.onclick = null;
      btnCancel.onclick = null;

      btnConfirm.onclick = () => {
        modal.style.display = 'none';
        form.classList.add('confirmed');
        form.submit(); // soumission sécurisée
      };

      btnCancel.onclick = () => {
        modal.style.display = 'none';
      };

      window.onclick = (e) => {
        if (e.target === modal) modal.style.display = 'none';
      };
    });
  });
});
