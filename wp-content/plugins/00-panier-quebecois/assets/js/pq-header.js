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

    $('#btn_empty_cart').click(function(e) {
        var msg = $(this).data('msg');
        if(confirm(msg) == false) {
            e.preventDefault();
        }   
    });
});