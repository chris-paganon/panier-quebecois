jQuery(function ($) {

  /* Loading products once page is ready after first load*/
  $(".pq_load_more_button").click(loadAllProducts);

  /* Adding products variation into cart with main product*/
  $(document).on("change", ".variations_form select", function () {
    var form_variations_data = $(this)
      .closest("form")
      .attr("data-product_variations");

    form_variations_data = JSON.parse(form_variations_data);
    
    var selected_attribute = $(this).attr("name");
    var selected_value = $(this).val();
    var selected_element = $(this);
    
    var has_variable_price = false;

    for (var i = 0; i < form_variations_data.length; i++) {
      if ( i > 0 && form_variations_data[i].display_price != form_variations_data[i-1].display_price ) {
        has_variable_price = true;
      }
    }

    for (var i = 0; i < form_variations_data.length; i++) {
      $.each(form_variations_data[i].attributes, function(form_attribute, form_value) {

        if ( selected_attribute == form_attribute && selected_value == form_value ) {
          selected_element.closest("form").find('[name="variation_id"]').val(form_variations_data[i].variation_id);

          if ( has_variable_price ) {
            selected_element
              .closest("form")
              .find("div.single_variation")
              .html(form_variations_data[i].price_html);
          }
        }
      });
    }
  });

  /* Load products function through ajax call*/  
  function loadAllProducts() {

    if ($(".categoryTitle.infiniteScroll").length) {
      $("#overlay").fadeIn(300);
    }

    var filters = [];
    $(".prod_filter:checked").each(function () {
      filters.push($(this).val());
    });
    var data = {
      action: "ajax_get_all_products",
      filters: filters.join(","),
    };

    $.post(
      pq_get_all_products_js_object.ajax_url,
      data,
      function (response_data) {
        if (response_data) {
          $(".pq_load_more_button").remove();
          $("#overlay").fadeOut(500);
          
          if ($(".infiniteScroll").length) {
            $(".infiniteScroll:last").after(response_data);
          } else {
            $(".parent-cat.panier-perso").append(response_data);
          }

          setTimeout(function () {
            $(".variations .pq-variation-wrapper select").each(function () {
              $(this).trigger("change");
            });
          }, 500);
        }
      }
    );
  }
});