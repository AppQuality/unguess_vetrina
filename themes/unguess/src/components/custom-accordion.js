const accordionTriggers = document.getElementsByClassName('accordion-trigger');
const accordionViewer = document.getElementById('accordion-viewer');

const getTemplate = target => {
    if ( target.getAttribute('template') ) {
        target.classList.add('accordion-trigger-active');
        const viewName = target.getAttribute('template');
        const view = document.querySelector(`[accordion-view="${viewName}"]`);
        view.classList.add('visible');
        return true;
    }
    if (target.parentNode) {
        return getTemplate(target.parentNode);
    }
    return false;
}

if ( accordionTriggers && accordionViewer ) {
    const showTemplate = event => {
        const views = accordionViewer.querySelectorAll('[accordion-view]');
        Array.from(views).forEach( view => view.classList.remove('visible') );
        Array.from(accordionTriggers).forEach(trigger => trigger.classList.remove('accordion-trigger-active') );
        const target = event.target;
        const template = getTemplate(target);
    }
    
    Array.from(accordionTriggers).forEach(trigger => {
        trigger.onclick = showTemplate;
    });
}