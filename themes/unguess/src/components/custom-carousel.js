const carousel = document.querySelector('.custom-element-carousel');
const container = carousel.firstChild;
const slides = container.children;

if (slides) {
    const slidesLength = slides.length;
    Array.from(slides).forEach(slide => {
        const slideClone = slide.cloneNode(true);
        container.append(slideClone);
    });

    let step = 0;
    const slidesWidth = slides[0].offsetWidth;

    const movement = () => {
        step++;
        if (step > slidesLength) {
            step = 1;
        }
        container.dataset.transition = 'on';
        container.style.transform = `translateX(-${slidesWidth * step}px)`;
        if (step == slidesLength) {
            setTimeout( () => {
                container.dataset.transition = 'off';
                container.style.transform = `translateX(-0px)`;
            }, 300);
        }
    };

    let interval = setInterval( movement, 2000 );

    const mouseOverHandler = () => {
        clearInterval(interval);
    };
    container.addEventListener( 'mouseover', mouseOverHandler );
    
    const mouseLeaveHandler = () => {
        interval = setInterval( movement, 2000 );
    };
    container.addEventListener( 'mouseleave', mouseLeaveHandler );

}