jQuery(document).ready(function ($) {
    
    /**
     * Hide no products found message on categories with child categories
     */

    if ( $.trim($('.awwm-product-loop .parent-cat').html()).length ) {
        $('.elementor-products-nothing-found').remove();
    }

    /**
     * Show onsale ribbon only for the right variation
     */
    $(document).on("change", ".variations_form select", function () {
        var form_variations_data = $(this)
          .closest("form")
          .attr("data-product_variations");
    
        form_variations_data = JSON.parse(form_variations_data);
        
        var selected_attribute = $(this).attr("name");
        var selected_value = $(this).val();
        var selected_element = $(this);
        var ribbon = selected_element.closest("li.product").find("span.onsale");
        
        var is_onsale = false;

        for (var i = 0; i < form_variations_data.length; i++) {
            $.each(form_variations_data[i].attributes, function(form_attribute, form_value) {
                if ( selected_attribute == form_attribute && selected_value == form_value ) {                
                    if ( form_variations_data[i].display_price != form_variations_data[i].display_regular_price ) {
                        ribbon.show();
                    } else {
                        ribbon.hide();
                    }
                }
                if ( form_variations_data[i].display_price != form_variations_data[i].display_regular_price ) {
                    is_onsale = true;
                }
            });
        }

        if (! is_onsale ) {
            ribbon.show();
        }
    });

    $(".variations .pq-variation-wrapper select").each(function () {
        $(this).trigger("change");
    });
});