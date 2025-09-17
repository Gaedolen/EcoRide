document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modal-suspendre');
  const modalClose = modal?.querySelector('.modal-close');
  const formSuspendre = document.getElementById('form-suspendre');
  const userIdInput = document.getElementById('user-id');
  const csrfInput = document.getElementById('csrf-token');
  const reasonSelect = document.getElementById('reason');
  const otherReasonInput = document.getElementById('other-reason');

  // Ouvrir modal via delegation (supporte les boutons ajoutés dynamiquement)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-suspend');
    if (!btn) return;
    const uid = btn.dataset.userId;
    const pseudo = btn.dataset.userPseudo || '';
    const csrfSuspend = btn.dataset.csrfSuspend || '';
    userIdInput.value = uid;
    csrfInput.value = csrfSuspend;
    document.getElementById('modal-user-pseudo').textContent = pseudo;
    // store unsuspend token for later DOM insertion
    modal.dataset.pendingUnsuspendToken = btn.dataset.csrfUnsuspend || '';
    modal.style.display = 'flex';
  });

  // fermer modal
  modalClose?.addEventListener('click', () => modal.style.display = 'none');
  modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

  // afficher champ "autres"
  reasonSelect?.addEventListener('change', e => {
    otherReasonInput.style.display = e.target.value === 'autres' ? 'block' : 'none';
  });

  // submit suspension
  formSuspendre?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const userId = userIdInput.value;
    const token = csrfInput.value;
    let reason = reasonSelect.value || '';
    if (reason === 'autres') reason = otherReasonInput.value || '';

    try {
      const res = await fetch(`/admin/utilisateur/suspendre/${encodeURIComponent(userId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _token: token, reason })
      });

      // lire la réponse brute et tenter parse JSON proprement
      const text = await res.text();
      let data;
      try { data = JSON.parse(text); }
      catch (err) {
        console.error('Réponse serveur inattendue:', text);
        alert('Erreur serveur : réponse invalide. Vérifiez console / network.');
        return;
      }

      if (!data.success) {
        alert('Erreur : ' + (data.error || 'Impossible de suspendre'));
        return;
      }

      // réussir -> remplacer le bouton par btn-reactivate (avec token unsuspend)
      const tr = document.querySelector(`button[data-user-id="${userId}"]`)?.closest('tr');
      if (!tr) { alert('OK, suspendu mais ligne introuvable'); return; }
      const td = tr.querySelector('td:last-child');

      // prefer token returned by server, else token stored on modal
      const unsuspendToken = data.unsuspendToken || modal.dataset.pendingUnsuspendToken || '';

      td.innerHTML = `
        <div class="user-actions">
          <span class="status suspended">Compte suspendu</span>
          <button class="btn-reactivate" data-user-id="${userId}" data-csrf-unsuspend="${unsuspendToken}">Réactiver</button>
        </div>
      `;

      modal.style.display = 'none';

    } catch (err) {
      console.error(err);
      alert('Erreur réseau / serveur lors de la suspension');
    }
  });

  // Réactivation via delegation
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-reactivate');
    if (!btn) return;
    const userId = btn.dataset.userId;
    const token = btn.dataset.csrfUnsuspend || '';

    if (!confirm('Confirmer la réactivation du compte ?')) return;

    try {
      const res = await fetch(`/admin/utilisateur/unsuspendre/${encodeURIComponent(userId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _token: token })
      });

      const text = await res.text();
      let data;
      try { data = JSON.parse(text); }
      catch (err) {
        console.error('Réponse serveur inattendue:', text);
        alert('Erreur serveur : réponse invalide.');
        return;
      }

      if (!data.success) {
        alert('Erreur : ' + (data.error || 'Impossible de réactiver'));
        return;
      }

      // réussite -> remettre bouton suspendre
      const tr = btn.closest('tr');
      const td = tr.querySelector('td:last-child');
      const pseudoCell = tr.querySelector('td:first-child');
      const pseudoText = pseudoCell ? pseudoCell.textContent.replace('⚠️','').trim() : '';

      // server returned new suspendToken (preferred), else fallback to existing data attribute
      const newSuspendToken = data.suspendToken || btn.dataset.csrfSuspend || '';

      td.innerHTML = `
        <button class="btn-suspend" 
                data-user-id="${userId}"
                data-user-pseudo="${pseudoText.replace(/"/g,'&quot;')}"
                data-csrf-suspend="${newSuspendToken}"
                data-csrf-unsuspend="${btn.dataset.csrfUnsuspend || ''}">
          Suspendre
        </button>
      `;
    } catch (err) {
      console.error(err);
      alert('Erreur réseau / serveur lors de la réactivation');
    }
  });

}); // DOMContentLoaded
