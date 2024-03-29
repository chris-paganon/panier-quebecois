jQuery(document).ready(function ($) {

    var navbar = $("#pq_sticky_navbar");
    var navbarHeight = navbar.height();
    
    // Get the offset position of the navbar
    var stickyNavTop = navbar.offset().top;

    //Run sticky navbar once on load in case we load inside the page
    pq_sticky_navbar();
    // When the user scrolls the page, execute myFunction
    window.onscroll = function() { pq_sticky_navbar() };

    // Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
    function pq_sticky_navbar() {
        var scrollTop = $(window).scrollTop();
        if (scrollTop >= stickyNavTop) {
            navbar.addClass("pq_sticky");
            navbar.closest("header.elementor").css("padding-bottom", navbarHeight);
        } else {
            navbar.removeClass("pq_sticky");
            navbar.closest("header.elementor").css("padding-bottom", "0");
        }
    }

    $(document).on('click','#btn_empty_cart',function(e) {
        var lang = $('html').attr('lang');
        lang = lang.slice(0,2);

        var msg = $(this).data('msg-'+lang);
        if(confirm(msg) == false) {
            e.preventDefault();
        }   
    });

    $('[data-clipboard]').on('click', function(e) {
        e.preventDefault();
        var copyText = $(this).data('clipboard');

        console.log(copyText);
        /* Copy the text inside the text field */
        navigator.clipboard.writeText(copyText);
    });
});