document.addEventListener("DOMContentLoaded", () => {
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
});
