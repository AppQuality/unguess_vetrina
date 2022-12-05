let triggers = document.getElementsByClassName('mega-menu-trigger');
let activeMenu = null;
let header = document.querySelector('[data-elementor-type="header"]');
let mmActive = null;

const closeMegaMenu = event => {
    if ( activeMenu ) {
        activeMenu.classList.remove('active');
        activeMenu = null;
        mmActive.classList.remove('mm-active');
        mmActive = null;
    }
};

const showMegaMenu = event => {
    mmActive = event.target;
    mmActive.classList.add('mm-active');
    const label = event.target.innerText.toLowerCase();
    const rect = event.target.getBoundingClientRect();
    const megaMenu = document.getElementById('menu-' + label);
    megaMenu.classList.add('active');
    megaMenu.style.cssText = 'left: ' + rect.left + 'px';
    activeMenu = megaMenu;
    megaMenu.onmouseleave = closeMegaMenu;
};

if (header) {
    header.onmouseleave = closeMegaMenu;
}

if (triggers) {
    let navs = triggers[0].parentNode.querySelectorAll(':not(.mega-menu-trigger) > a.elementor-item');
    Array.from(navs).forEach( nav => {
        nav.onmouseover = closeMegaMenu;
    } );
}

Array.from(triggers).forEach( trigger => {
    trigger.onmouseover = showMegaMenu;
} );