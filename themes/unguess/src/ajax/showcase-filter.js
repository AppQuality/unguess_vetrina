const showcasesFilterScript = () => {
    const showcasesFilter = document.querySelectorAll('.filter-showcases');
        let industryId = [];
        let useCaseId = [];

        const setTermsId = (item, filter) => {
                let array;
                if ( filter == 'industry' ) {
                        array = industryId;
                } else {
                        array = useCaseId;
                }
                setArrayFilter(array, item);
        };

        const setArrayFilter = (array, item) => {
                if(!array.includes(item)) {
                        array.push(item);
                } else {
                        array.splice(array.indexOf(item), 1);
                }
        }

        Array.from(showcasesFilter).forEach( filter => {
                const showcasesFilterTriggers = filter.querySelectorAll('input');
                const showcasesList = document.querySelector('.showcases-list');

                const showcasesFilterHandler = event => {
                        setTermsId(event.target.value, event.target.dataset.filter);
                        jQuery.ajax({
                                type: "POST",
                                url: ajax.url,
                                data: {
                                        action: 'showcases_filter_callback',
                                        taxonomy: event.target.dataset.taxonomy,
                                        industryId: industryId,
                                        useCaseId: useCaseId,
                                },
                                beforeSend: () => {
                                        const loader = document.createElement('div');
                                        loader.className = 'loader';
                                        loader.innerHTML = '<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';
                                        showcasesList.prepend(loader);
                                },
                                success: response => {
                                        showcasesList.innerHTML = response.data;
                                },
                                error: error => {
                                        console.error(error);
                                }
                        });
                };

                Array.from(showcasesFilterTriggers).forEach( trigger => {
                        trigger.onchange = showcasesFilterHandler;
                });

        });

};
window.addEventListener('load', showcasesFilterScript );