document.addEventListener('DOMContentLoaded', () => {

    // Pop-ups
    const popups = {
        avis: {
            overlay: document.getElementById('avis-popup'),
            form: document.getElementById('avis-form'),
            closeBtn: document.querySelector('#avis-popup .close-popup'),
            cibleIdInput: document.getElementById('cible_id'),
            covoiturageIdInput: document.getElementById('covoiturage_id')
        },
        report: {
            overlay: document.getElementById('report-popup'),
            form: document.getElementById('report-form'),
            closeBtn: document.getElementById('close-report-popup'),
            reportedUserIdInput: document.getElementById('reportedUserId'),
            covoiturageIdInput: document.getElementById('covoiturageId')
        }
    };

    // Ouverture pop-ups
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('open-avis-popup')) {
            e.preventDefault();
            const btn = e.target;
            popups.avis.cibleIdInput.value = btn.dataset.cibleId;
            popups.avis.covoiturageIdInput.value = btn.dataset.avisCovoiturageId;
            popups.avis.overlay.style.display = 'flex';
        }

        if (e.target.classList.contains('open-report-popup')) {
            e.preventDefault();
            const btn = e.target;
            popups.report.reportedUserIdInput.value = btn.dataset.reportedUserId;
            popups.report.covoiturageIdInput.value = btn.dataset.covoiturageId;
            popups.report.overlay.style.display = 'flex';
        }
    });

    // Fermeture Pop-ups
    Object.values(popups).forEach(popup => {
        if (popup.closeBtn) {
            popup.closeBtn.addEventListener('click', () => popup.overlay.style.display = 'none');
        }
    });

    window.addEventListener('click', e => {
        Object.values(popups).forEach(popup => {
            if (e.target === popup.overlay) popup.overlay.style.display = 'none';
        });
    });

    // Soumission formulaires AJAX
    function handleFormSubmit(popup, badgeClass, badgeText, getButtonSelector) {
        let isSubmitting = false;

        popup.form.addEventListener('submit', e => {
            e.preventDefault();

            if (isSubmitting) return; // évite le double clic
            isSubmitting = true;

            const formData = new URLSearchParams(new FormData(popup.form));

            fetch(popup.form.action, {
                method: 'POST',
                body: formData,
            })
            .then(res => res.json())
            .then(data => {
                if (data.success === true) {
                    // Succès → on ferme + badge
                    popup.overlay.style.display = 'none';
                    popup.form.reset();

                    const button = document.querySelector(getButtonSelector());
                    if (button) {
                        const badge = document.createElement('span');
                        badge.className = badgeClass;
                        badge.textContent = badgeText;
                        button.replaceWith(badge);
                    }
                } else if (data.success === 'exists') {
                    // Déjà existant → juste fermer et reset, pas d'alerte
                    popup.overlay.style.display = 'none';
                    popup.form.reset();
                } else {
                    alert(data.message || 'Erreur lors de l\'envoi.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erreur réseau lors de l\'envoi.');
            })
            .finally(() => {
                isSubmitting = false;
            });
        });
    }

    // Avis
    handleFormSubmit(
        popups.avis,
        'badge badge-warning',
        '⏳ Avis en attente',
        () => `.open-avis-popup[data-avis-covoiturage-id="${popups.avis.covoiturageIdInput.value}"][data-cible-id="${popups.avis.cibleIdInput.value}"]`
    );

    // Signalement
    handleFormSubmit(
        popups.report,
        'badge badge-warning',
        '⏳ Signalement en attente',
        () => `.open-report-popup[data-reported-user-id="${popups.report.reportedUserIdInput.value}"][data-covoiturage-id="${popups.report.covoiturageIdInput.value}"]`
    );

});
