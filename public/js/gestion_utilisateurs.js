document.addEventListener('DOMContentLoaded', () => {

    // MODAL SUSPENDRE
    const modalSuspendre = document.getElementById('modal-suspendre');
    const closeSuspendre = modalSuspendre.querySelector('.modal-close');

    document.querySelectorAll('.btn-suspend').forEach(btn => {
        btn.addEventListener('click', () => {
            modalSuspendre.style.display = 'flex';
            document.getElementById('modal-user-pseudo').textContent = btn.dataset.userPseudo;
            document.getElementById('user-id').value = btn.dataset.userId;
            document.getElementById('csrf-token').value = btn.dataset.csrfToken;
        });
    });

    // Fermer la modal suspendre
    closeSuspendre.addEventListener('click', () => modalSuspendre.style.display = 'none');
    modalSuspendre.addEventListener('click', (e) => { if (e.target === modalSuspendre) modalSuspendre.style.display = 'none'; });

    // Champ "Autres"
    document.getElementById('reason').addEventListener('change', (e) => {
        document.getElementById('other-reason').style.display = e.target.value === 'autres' ? 'block' : 'none';
    });

    // Soumission AJAX suspendre
    document.getElementById('form-suspendre').addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = document.getElementById('user-id').value;
        const csrfToken = document.getElementById('csrf-token').value;
        const reason = document.getElementById('reason').value;
        const otherReason = document.getElementById('other-reason').value;

        try {
            const response = await fetch(`/admin/utilisateur/suspendre/${userId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason, otherReason, _token: csrfToken })
            });
            const data = await response.json();

            if(data.success){
                modalSuspendre.style.display = 'none';
                const td = document.querySelector(`.btn-suspend[data-user-id="${userId}"]`).closest('td');
                td.innerHTML = `
                    <div class="user-actions" data-user-id="${userId}">
                        <span class="status suspended">Compte suspendu</span>
                        <form method="post" action="/admin/utilisateur/${userId}/unsuspendre" class="unsuspend-form">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn-reactivate">Réactiver</button>
                        </form>
                    </div>
                `;
            } else {
                alert('Erreur : ' + data.error);
            }
        } catch(err) {
            alert('Erreur serveur : ' + err);
        }
    });
});
