document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('.hidden-input');
    const fileNameDisplay = document.getElementById('file-name');

    if (input && fileNameDisplay) {
        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = "Aucun fichier choisi";
            }
        });
    }
});
