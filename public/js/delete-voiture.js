document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("confirmation-modal");
    const btnYes = document.getElementById("confirm-yes");
    const btnNo = document.getElementById("confirm-no");

    let currentForm = null;

    document.querySelectorAll(".btn-supprimer").forEach(button => {
        button.addEventListener("click", () => {
            currentForm = button.closest("form");
            modal.classList.remove("hidden");
        });
    });

    btnYes.addEventListener("click", () => {
        if (currentForm) {
            currentForm.submit();
        }
    });

    btnNo.addEventListener("click", () => {
        modal.classList.add("hidden");
        currentForm = null;
    });
});
