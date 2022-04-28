/**
 * Make optional bundle item button fully clickable
 */

jQuery(document).ready(function ($) {
  $(".bundled_item_optional").click(pq_check_bundle_item);

  function pq_check_bundle_item(event) {
    //Only run if the event clicked is not the checkbox or its label
    if (!$(event.target).closest("label.bundled_product_optional_checkbox").length > 0 && !$(event.target).closest("div.cart").length > 0) {
      $(this).find(".bundled_product_checkbox").click();
    }
  }

  $(".pq-discovery-add-to-cart .bundle_add_to_cart_button").click(
    pq_bundle_submit_loader
  );

  function pq_bundle_submit_loader(event) {
    $(this).addClass("my-spinner");
  }
});
