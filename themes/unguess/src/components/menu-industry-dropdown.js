const menuIndustry = document.getElementsById('home-select-industry');
const menuIndustryTrigger = menuIndustry.querySelector('.menu-industry-dropdown-trigger');

const menuIndustryDropdownHandler = event => {
    if ( menuIndustry.classList.contains('menu-industry-active') ) {
        menuIndustry.classList.remove('menu-industry-active');
    } else {
        menuIndustry.classList.add('menu-industry-active');
    }
};

menuIndustryTrigger.onclick = menuIndustryDropdownHandler;