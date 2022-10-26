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
            var isSelected = $(this).hasClass( "tab_selected" );
            option.val(link.attr('href'));
            option.html(link.html());
            if(isSelected){
                option.attr('selected','selected')
            }
            MyAccountMobileNavMenu.append(option);
        });

        MyAccountMobileNavMenu.change(function() {
            console.log('change', $(this).val());
            location = $(this).val();
        });

        $("#tgwc-woocommerce").prepend(MyAccountMobileNavMenu); 
    }
});
