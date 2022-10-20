jQuery(function($){
    $(document).ready(function( ) {
        MyAccountMobileMenu();
    });

    function MyAccountMobileMenu()
    {
        var MyAccountMobileNavMenu = $(document.createElement('select')).prop({
            id: 'pq-myAccount-mobile-nav',
            name: 'pq-myAccount-mobile-nav'
        });

        $(".tgwc-woocommerce-MyAccount-navigation-wrap > ul.scroll_tabs_container > .scroll_tab_inner > li").each(function( index ) {
            var link = $(this).find('a:first');
            var option = $(document.createElement('option'));

            option.val(link.attr('href'));
            option.html(link.html());
            MyAccountMobileNavMenu.append(option);
        });

        MyAccountMobileNavMenu.change(function() {
            console.log('change', $(this).val());
            location = $(this).val();
        });

        $("#tgwc-woocommerce").prepend(MyAccountMobileNavMenu); 
    }
});
