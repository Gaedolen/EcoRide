document.addEventListener("DOMContentLoaded", () => {

    // Pour les couleurs

    const trigger = document.querySelector(".custom-select-trigger");
    const options = document.querySelector(".custom-options");
    const hiddenInput = document.getElementById("voiture_couleur");

    if (!trigger || !options || !hiddenInput) return;

    // Sélection automatique de la première couleur par défaut
    const firstOption = options.querySelector("li");
    if (firstOption) {
        const defaultValue = firstOption.getAttribute("data-value");
        const defaultLabel = firstOption.textContent.trim();

        hiddenInput.value = defaultValue;
        trigger.textContent = defaultLabel;
    }

    // Afficher / cacher la liste au clic
    trigger.addEventListener("click", () => {
        options.style.display = options.style.display === "block" ? "none" : "block";
    });

    // Quand on clique sur une couleur, on met à jour le champ caché et le texte affiché
    options.querySelectorAll("li").forEach(li => {
        li.addEventListener("click", () => {
            const value = li.getAttribute("data-value");
            trigger.textContent = li.textContent.trim();
            hiddenInput.value = value;
            options.style.display = "none";
        });
    });

    // Fermer la liste si on clique en dehors
    document.addEventListener("click", e => {
        if (!e.target.closest(".custom-select-wrapper")) {
            options.style.display = "none";
        }
    });

    // Vérifier qu'une couleur a bien été choisie avant de soumettre le formulaire
    const form = document.querySelector("form");
    form.addEventListener("submit", function (e) {
        if (!hiddenInput.value) {
            alert("Veuillez sélectionner une couleur !");
            e.preventDefault();
        }
    });

    // Pour les préférences

    const addPrefBtn = document.getElementById('add-pref');
    const prefList = document.getElementById('preferences-list');
    if (addPrefBtn && prefList) {
        let index = prefList.children.length;

        addPrefBtn.addEventListener('click', () => {
            // Récupération du prototype Twig
            const prototype = prefList.dataset.prototype;
            const newForm = prototype.replace(/__name__/g, index);
            const li = document.createElement('li');
            li.innerHTML = newForm + '<button type="button" class="remove-pref">❌</button>';
            prefList.appendChild(li);

            li.querySelector('.remove-pref').addEventListener('click', () => li.remove());
            index++;
        });

        // Supprimer les préférences déjà existantes
        prefList.querySelectorAll('.remove-pref').forEach(btn => {
            btn.addEventListener('click', () => btn.parentElement.remove());
        });
    }
});
