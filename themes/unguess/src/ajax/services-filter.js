const servicesFilterScript = () => {
    const servicesFilter = document.querySelector('.services-filter');
    const servicesFilterTriggers = servicesFilter.querySelectorAll('input');
    const servicesList = document.querySelector('.services-list');

    const serviceFilterHandler = event => {
        jQuery.ajax({
            type: "POST",
            url: ajax.url,
            data: {
                action: 'services_filter_callback',
                useCaseId: event.target.value,
				industryId: event.target.dataset.industry
            },
            beforeSend: () => {
                const loader = document.createElement('div');
                loader.className = 'loader';
                loader.innerHTML = '<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';
                servicesList.prepend(loader);
            },
            success: response => {
                servicesList.innerHTML = response.data;
            },
            error: error => {
                console.error(error);
            }
        });
    };

    Array.from(servicesFilterTriggers).forEach( trigger => {
        trigger.onchange = serviceFilterHandler;
    });
};
window.addEventListener('load', servicesFilterScript );