document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('.hidden-input');
    const fileNameDisplay = document.getElementById('file-name');
    const imagePreview = document.getElementById('photo-preview');

    if (input && fileNameDisplay) {
        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];

                // Affiche le nom du fichier
                fileNameDisplay.textContent = file.name;

                // On vérifie que c’est une image
                if (file.type.startsWith('image/') && imagePreview) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };

                    reader.readAsDataURL(file);
                }
            } else {
                fileNameDisplay.textContent = "Aucun fichier choisi";
                if (imagePreview) {
                    imagePreview.style.display = 'none';
                    imagePreview.src = '';
                }
            }
        });
    }
});
