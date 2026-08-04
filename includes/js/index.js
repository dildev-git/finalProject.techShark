document.addEventListener('DOMContentLoaded', function () {
    // Initialize banner slider
    const bannerSlider = new Swiper('.banner-slider', {
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
});
