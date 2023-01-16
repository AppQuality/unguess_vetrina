const listPostCarousels = document.getElementsByClassName('list-post-carousel');

Array.from(listPostCarousels).forEach( listPostCarousel => {
        const listCards = listPostCarousel.querySelectorAll('.service-card, .showcase-card');
        const listPostCarouselTriggers = listPostCarousel.querySelectorAll('.list-post-scroll-trigger');
        const listPostCarouselContent = listPostCarousel.querySelector('.list-post-carousel-content');
        const listPostCarouselLength = listCards.length;
        let position = 0;

        const setPosition = event => {
                const target = event.currentTarget;
                const move = target.dataset.move;
                if ( move == 'prev' ) {
                        position--;
                } else {
                        position++;
                }
                setTriggerOff();
        };

        const setTriggerOff = () => {
                let maxPosition = listPostCarouselLength - 1;
                let maxLimit = 1;
                if ( window.innerWidth > 1024 ) {
                        maxPosition = listPostCarouselLength - 3;
                        maxLimit = 3;
                }
                if ( position == 0 ) {
                        listPostCarouselTriggers[0].classList.add('off');
                } else {
                        listPostCarouselTriggers[0].classList.remove('off');
                }
                if ( position == maxPosition ) {
                        listPostCarouselTriggers[1].classList.add('off');
                } else {
                        listPostCarouselTriggers[1].classList.remove('off');
                }
                if ( listPostCarouselLength <= maxLimit ) {
                        listPostCarouselTriggers[1].classList.add('off');
                }
        };

        setTriggerOff();

        const moveListPostCarousel = event => {
                const target = event.currentTarget;
                const move = target.dataset.move;
                let maxPosition = listPostCarouselLength - 1;
                if ( window.innerWidth > 1024 ) {
                        maxPosition = listPostCarouselLength - 3;
                }
                console.log(move)
                if ( move == 'prev' && position > 0 
                   || move == 'next' && position < maxPosition ) {
                        setPosition(event);
                        const movement = position * (listCards[0].getBoundingClientRect().width + 24);
                        listPostCarouselContent.style.transform = `translateX(${-movement + 'px'})`;
                }
        };

        Array.from(listPostCarouselTriggers).forEach( trigger => {
                trigger.onclick = moveListPostCarousel;
        });

});