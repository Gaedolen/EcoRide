function adjustHeroTextHeight() {
    const img = document.querySelector('.hero-img-container img');
    const text = document.querySelector('.hero-text');
    if(img && text){
        text.style.height = img.clientHeight + 'px';
    }
}

window.addEventListener('load', adjustHeroTextHeight);
window.addEventListener('resize', adjustHeroTextHeight);
