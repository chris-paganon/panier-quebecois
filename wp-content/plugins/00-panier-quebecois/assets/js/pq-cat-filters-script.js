(function ($) {

  /**
   * Adding URL parameters with filter boxes
   */
  $(".prod_filter").change(function () {
    if(!this.checked) {
      $('.prod_filter[value="' + $(this).val() + '"]').prop('checked', this.checked);
    }
    var filters = [];
    $(".prod_filter:checked:visible").each(function () {
      filters.push($(this).val());
    });

    window.location.href = "?filters=" + filters;
  });

  /* Left side sub-menu click control on desktop */
  if ($(window).width() > 1025) {
    $(".menu-item-has-children .sub-arrow").click(function(e){
      e.preventDefault();
      e.stopPropagation();
      $(e.target).closest(".menu-item-has-children").toggleClass("pq-active");
    })
  }

  /* Script controlling view of categories left side menu on different devices*/
  if ($(window).width() < 1025) {
    $("#slideDown-opened").attr("id", "slideDown");
  } else {
    $('#slideDown').show();
    $("#slideDown").attr("id", "slideDown-opened");
  }
  $(window).resize(function(){
    if ($(window).width() < 1025) {
      $("#slideDown-opened").attr("id", "slideDown");
    } else {
      $('#slideDown').show();
      $("#slideDown").attr("id", "slideDown-opened");
    }
  });

  $('#clickCat').click(function(e) {
    if ($(window).width() < 1025) {
      e.preventDefault();
      e.stopPropagation();
      $(this).toggleClass('dropdownActive');
      $('#slideDown').slideToggle('slow');
    }
  });
  $('#slideDown a').click(function(e) {
    if ($(window).width() < 1025) {
      e.stopPropagation();
      $('#slideDown').slideToggle('slow');
      $('#clickCat').toggleClass('dropdownActive');
    }
  });
  $('#slideDown .menu-item-has-children').click(function(e) {
    if ($(window).width() < 1025) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass('parentActive');
      $(this).siblings().removeClass('parentActive');
    }
  });

  /* Script controlling view of filters left side menu on different devices*/

  if ($(window).width() < 1025) {
    $("#slideDownFilter-opened").attr("id", "slideDownFilter");
  } else {
    $('#slideDownFilter').show();
    $("#slideDownFilter").attr("id", "slideDownFilter-opened");
  }

  $(window).resize(function(){
    if ($(window).width() < 1025) {
      $("#slideDownFilter-opened").attr("id", "slideDownFilter");
    } else {
      $('#slideDownFilter').show();
      $("#slideDownFilter").attr("id", "slideDownFilter-opened");
    }
  });
  if ($(window).width() < 1025) {
    $('#slideDownFilter').hide('');
    $('#clickFilter').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).toggleClass('dropdownActive');
        $('#slideDownFilter').slideToggle('slow');
    });
    $(window).scroll(function (event) {
      var scroll = $(window).scrollTop();
      if (scroll>250) {
        $('#secCat').addClass('posFixed');
      } else {
        $('#secCat').removeClass('posFixed');
      }
    });
  } else {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const getFilters = urlParams.get('filters');
    $('#slideDownFilter-opened').show();
    $('#clickFilter').addClass('clickFilterActive');
    $('#clickFilter').addClass('open');
    $(window).scroll(function (event) {
        var scroll = $(window).scrollTop();
        if($(window).width() > 1026) {
          if (scroll>250) {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const getFilters = urlParams.get('filters');
            if($('#slideDownFilter-opened').is(':visible') && !$('#clickFilter').hasClass('open')) {
              $('#slideDownFilter-opened').hide();
              $('#clickFilter').removeClass('clickFilterActive');
            }
            if($('#slideDownFilter-opened').is(':visible')) {
              $('#clickFilter').addClass('clickFilterActive');
            }
            if(getFilters !== undefined && getFilters !== ''){
              $('#clickFilter').on('click', function(e) {
                  e.stopImmediatePropagation();
                  e.preventDefault();
                  if($('#slideDownFilter-opened').is(':visible')) {
                    $('#slideDownFilter-opened').hide();
                    $(this).removeClass('clickFilterActive');
                    $(this).removeClass('open');
                  } else {
                    $(this).removeClass('open');
                    $('#slideDownFilter-opened').show();
                    $(this).addClass('clickFilterActive');
                    $(this).addClass('open');
                  }
              });
            } else {
              $('#clickFilter').on('click', function(e) {
                e.stopImmediatePropagation();
                e.preventDefault();
                if($('#slideDownFilter-opened').is(':visible')) {
                  $('#slideDownFilter-opened').hide();
                  $(this).removeClass('clickFilterActive');
                  $(this).removeClass('open');
                } else {
                  $('#slideDownFilter-opened').show();
                  $(this).addClass('clickFilterActive');
                  $(this).addClass('open');
                }
              });
            }
          } else {
            $('#clickFilter').off('click');
            $('#slideDownFilter-opened').show();
          }
        }
    });
  }
})(jQuery);