document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('contactModal');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('contactForm');

    document.querySelectorAll('.btn-contact').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const pseudo = btn.getAttribute('data-pseudo');
            const userId = btn.getAttribute('data-id');

            // Mettre le titre
            modalTitle.textContent = `Envoyer un mail à ${pseudo}`;

            // Mettre l'action du formulaire vers Symfony
            form.action = `/employe/contacter/${userId}`;

            // Ouvrir la modal
            modal.style.display = 'flex';
        });
    });

    // Fermer la modal
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => { if(e.target === modal) modal.style.display = 'none'; });

    // Sélection de tous les boutons traiter/ignorer
    document.querySelectorAll('.btn-traiter, .btn-ignorer').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault(); // éviter le submit classique
            const form = btn.closest('form'); // récupérer le formulaire parent

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => {
                if (response.ok) {
                    // Remplacer les deux boutons par le texte correspondant
                    const td = form.parentElement; // la cellule <td>
                    if (btn.classList.contains('btn-traiter')) {
                        td.innerHTML = 'Signalement traité';
                    } else if (btn.classList.contains('btn-ignorer')) {
                        td.innerHTML = 'Signalement ignoré';
                    }
                } else {
                    alert('Erreur serveur, réessayez.');
                }
            })
            .catch(() => {
                alert('Erreur réseau, réessayez.');
            });
        });
    });

    // Champ de recherche dynamique pour la sélection d'utilisateur
    const input = document.querySelector('#{{ form.reportedUser.vars.id }}');
    const dropdown = document.createElement('div');
    dropdown.style.position = 'absolute';
    dropdown.style.backgroundColor = '#fff';
    dropdown.style.border = '1px solid #ccc';
    dropdown.style.zIndex = '1000';
    dropdown.style.maxHeight = '150px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.width = input.offsetWidth + 'px';
    dropdown.style.display = 'none';
    input.parentNode.appendChild(dropdown);

    input.addEventListener('input', function() {
        const value = this.value;
        if (value.length < 1) {
            dropdown.style.display = 'none';
            return;
        }

        fetch('{{ path("employe_user_search") }}?q=' + encodeURIComponent(value))
            .then(response => response.json())
            .then(data => {
                dropdown.innerHTML = '';
                data.forEach(user => {
                    const item = document.createElement('div');
                    item.textContent = user.pseudo;
                    item.style.padding = '5px';
                    item.style.cursor = 'pointer';
                    item.addEventListener('click', () => {
                        input.value = user.id;
                        dropdown.style.display = 'none';
                    });
                    dropdown.appendChild(item);
                });
                dropdown.style.display = data.length ? 'block' : 'none';
            });
    });

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== input) {
            dropdown.style.display = 'none';
        }
    });
});

