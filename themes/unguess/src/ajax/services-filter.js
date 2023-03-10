const servicesFilterScript = () => {
	const servicesList = document.querySelector('.services-list');
	
	const serviceSelectContainer = document.querySelector('.services-select-container');
	const serviceSelected = document.querySelector('.services-selected');

	const fakeSelectHandler = (remove = false) => {
		if ( serviceSelectContainer.classList.contains('active') || remove ) {
			serviceSelectContainer.classList.remove('active');
		} else {
			serviceSelectContainer.classList.add('active');
		}
	};
	
	const setChecked = (filters, value) => {
		Array.from(filters).forEach( filter => {
			if ( filter.value == value ) {
				filter.checked = true;
			} else {
				filter.checked = false;
			}
		});
	}

	serviceSelected.onclick = () => fakeSelectHandler(false);
	
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
				serviceSelected.innerText = event.target.dataset.name;
				if ( event.target.dataset.device == 'desk' ) {
					setChecked(servicesOptionTriggers, event.target.value);
				} else {
					setChecked(servicesFilterTriggers, event.target.value);
					fakeSelectHandler(false);
				}
            },
            error: error => {
                console.error(error);
            }
        });
    };
	
	serviceSelectContainer.onmouseleave = fakeSelectHandler(true);

	const servicesFilter = document.querySelector('.services-filter');
	const servicesFilterTriggers = servicesFilter.querySelectorAll('input');
	Array.from(servicesFilterTriggers).forEach( trigger => {
		trigger.onchange = serviceFilterHandler;
	});

	const servicesOptionTriggers = document.querySelectorAll('.services-option input');
	Array.from(servicesOptionTriggers).forEach( trigger => {
		trigger.onchange = serviceFilterHandler;
	});
};
window.addEventListener('load', servicesFilterScript );