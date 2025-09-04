function adjustHeroText() {
    const textContainer = document.querySelector('.hero-text');
    const h1 = textContainer.querySelector('h1');
    const p = textContainer.querySelector('p');
    const btn = textContainer.querySelector('.btn-cta');

    // Hauteur disponible pour le texte
    const paddingTop = parseInt(window.getComputedStyle(textContainer).paddingTop);
    const paddingBottom = parseInt(window.getComputedStyle(textContainer).paddingBottom);
    const maxHeight = textContainer.clientHeight - paddingTop - paddingBottom;

    // Hauteur totale du texte
    let totalHeight = h1.offsetHeight + p.offsetHeight + btn.offsetHeight + 20;

    // Réduire proportionnellement seulement si le texte dépasse
    if(totalHeight > maxHeight){
        const scale = maxHeight / totalHeight;
        h1.style.fontSize = `${3.2 * scale}rem`;
        p.style.fontSize = `${1.35 * scale}rem`;
        btn.style.fontSize = `${1.2 * scale}rem`;
    } else {
        // Revenir à la taille max si possible
        h1.style.fontSize = '3.2rem';
        p.style.fontSize = '1.35rem';
        btn.style.fontSize = '1.2rem';
    }
}

window.addEventListener('load', adjustHeroText);
window.addEventListener('resize', adjustHeroText);
