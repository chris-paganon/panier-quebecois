/**
 * Additional info menu on products single
 */

jQuery(document).ready(function( $ ) {
    $('nav h4.pq_additonal_info_title').click(pq_additional_info_menu);

    function pq_additional_info_menu(event) {
        $(this).addClass('pq_active');
        $(this).siblings().removeClass('pq_active');

        navClasses = this.className.split(/\s+/);
        infoBlocks = $(this).closest('.pq_product_additional_info_wrapper').find('.pq_additional_info_block_wrapper');

        infoBlocks.each(function(){

            for (var i = 0; i < navClasses.length; ++i) {
                if ( $(this).hasClass(navClasses[i]) ) {
                    $(this).addClass('pq_active');
                    $(this).siblings().removeClass('pq_active');
                }
            }
        });
    }

    // if($('body').hasClass('home') && $('body').hasClass('logged-in')){
        if(!sessionStorage.getItem('dashboard_seen')){
            sessionStorage.setItem('dashboard_seen', true);
            window.location.replace('/mon-compte/');
        }
    // }
});