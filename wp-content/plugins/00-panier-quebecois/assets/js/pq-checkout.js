jQuery(document).ready(function ($) {

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                // element added to DOM
                var nodes = $( mutation.addedNodes ); // jQuery set
                nodes.each(function() {
                    var node = $( this );

                    if( node.is('#wc-od') ) {

                        delivery_form = node.find('input#delivery_date');
                        delivery_time_form = node.find('select#delivery_time_frame');

                        //Trigger if only the delivery form is found
                        if ( delivery_form.length > 0 && delivery_time_form.length == 0 ) {
                            $( 'body' ).trigger( 'update_checkout' );
                        }
                    }
                });
            }
        });
    });
    
    var config = {
        attributes: true,
        childList: true,
        characterData: true,
        subtree: true,
    };
    
    observer.observe($('.woocommerce-billing-fields')[0], config);
});