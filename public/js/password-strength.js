document.addEventListener("DOMContentLoaded", function () { // Vérification de la force du mot de passe
    const passwordInput = document.querySelector('[name="registration_form[plainPassword]"]');
    const bar = document.getElementById("password-strength-bar");
    const text = document.getElementById("password-strength-text");

    if (!passwordInput || !bar || !text) {
        console.error("Un ou plusieurs éléments introuvables.");
        return;
    }

    passwordInput.addEventListener("input", () => {
        const pwd = passwordInput.value;
        let score = 0;

        if (pwd.length >= 8) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[a-z]/.test(pwd)) score++;
        if (/\d/.test(pwd)) score++;
        if (/[\W_]/.test(pwd)) score++;

        if (score <= 1) {
            bar.style.backgroundColor = "#e74c3c";
            text.textContent = "Trop faible";
            text.style.color = "#e74c3c";
        } else if (score <= 3) {
            bar.style.backgroundColor = "#f39c12";
            text.textContent = "Moyen";
            text.style.color = "#f39c12";
        } else {
            bar.style.backgroundColor = "#2ecc71";
            text.textContent = "Fort";
            text.style.color = "#2ecc71";
        }
    });
});

document.addEventListener("DOMContentLoaded", function () { // Pop-up pour champ manquant
    const form = document.querySelector("form");

    form.addEventListener("submit", function (e) {
        // Vérification de l'email valide
        const emailInput = document.getElementById('registration_form_email');
        if (emailInput) {
            const email = emailInput.value.trim();
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!regexEmail.test(email)) {
                e.preventDefault();
                alert("Veuillez saisir une adresse email valide.");
                emailInput.focus();
                return;
            }
        }
        const champsObligatoires = [
            { id: "registration_form_email", nom: "Email" },
            { id: "registration_form_plainPassword", nom: "Mot de passe" },
            { id: "registration_form_nom", nom: "Nom" },
            { id: "registration_form_prenom", nom: "Prénom" },
            { id: "registration_form_date_naissance", nom: "Date de naissance" },
            { id: "registration_form_adresse", nom: "Adresse" },
            { id: "registration_form_telephone", nom: "Téléphone" },
            { id: "registration_form_pseudo", nom: "Pseudo" }
        ];

        for (let champ of champsObligatoires) {
            const input = document.getElementById(champ.id);
            if (!input || input.value.trim() === "") {
                e.preventDefault();
                alert(`Vous devez remplir le champ ${champ.nom}.`);
                input.focus();
                return;
            }
        }
    });
});

document.addEventListener("DOMContentLoaded", function () { // Vérification de l'âge
    const form = document.querySelector("form");
    const dateInput = document.getElementById("registration_form_date_naissance");
    const ageError = document.getElementById("age-error");

    if (!form || !dateInput || !ageError) return;

    form.addEventListener("submit", function (e) {
        const value = dateInput.value;
        if (!value) return;

        const birthDate = new Date(value);
        const today = new Date();

        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        const dayDiff = today.getDate() - birthDate.getDate();

        if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
            age--;
        }

        if (age < 18) {
            e.preventDefault();
            ageError.textContent = "Vous devez avoir au moins 18 ans pour vous inscrire.";
            ageError.style.color = "#e74c3c";
        } else {
            ageError.textContent = ""; // efface le message si tout va bien
        }
    });
});