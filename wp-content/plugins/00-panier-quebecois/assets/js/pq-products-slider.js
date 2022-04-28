jQuery(document).ready(function ($) {
    $('.pq-products-slider ul.products').slick({
        infinite: true,
        slidesToShow: 5,
        slidesToScroll: 1,
        swipeToSlide: true,
        responsive: [
            {
                breakpoint: 1024,
                    settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 440,
                settings: {
                    slidesToShow: 2,
                }
            },
        ]
    });

    $('button.slick-arrow').empty();
});