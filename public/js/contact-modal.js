document.addEventListener('DOMContentLoaded', () => {
    // Ouvrir la modal
    document.querySelectorAll('.btn-contact').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const modal = document.getElementById(targetId);
            if(modal) modal.style.display = 'flex';
        });
    });

    // Fermer la modal
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').style.display = 'none';
        });
    });

    // Fermer si on clique en dehors du contenu
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if(e.target === overlay) overlay.style.display = 'none';
        });
    });
});
