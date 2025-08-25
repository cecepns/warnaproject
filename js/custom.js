(function ($) {
  "use strict";


  // var client_logo = client_logo_slider

  var client_logo = $('.client_logo_slider')
  if (client_logo.length) {
    client_logo.owlCarousel({
      items: 6,
      loop: true,
      responsive: {
        0: {
          items: 3,
          margin: 15,
        },
        600: {
          items: 3,
          margin: 15,
        },
        991: {
          items: 5,
          margin: 15,
        },
        1200: {
          items: 6,
          margin: 15,
        }
      }
    });
  }



  // var review = $('.review_slider');
  // if (review.length) {
  //   review.owlCarousel({
  //     items: 1,
  //     loop: true,
  //     dots: true,
  //     autoplay: false,
  //     autoplayHoverPause: true,
  //     autoplayTimeout: 5000,
  //     nav: false,
  //   });
  // }

  $(document).ready(function () {
    $('select').niceSelect();
  });
  // menu fixed js code
  $(window).scroll(function () {
    var window_top = $(window).scrollTop() + 1;
    if (window_top > 50) {
      $('.main_menu').addClass('menu_fixed animated fadeInDown');
    } else {
      $('.main_menu').removeClass('menu_fixed animated fadeInDown');
    }
  });

  // $('.img-gal').magnificPopup({
  //   type: 'image',
  //   gallery: {
  //     enabled: true
  //   }
  // });

  // Search Toggle
  $("#search_input_box").hide();
  $("#search_1").on("click", function () {
    $("#search_input_box").slideToggle();
    $("#search_input").focus();
  });
  $("#close_search").on("click", function () {
    $('#search_input_box').slideUp(500);
  });

  //------- Mailchimp js --------//  
  function mailChimp() {
    $('#mc_embed_signup').find('form').ajaxChimp();
  }
  mailChimp();

  var filter_item = $('.filtr-container');
  if (filter_item.length) {
    var filterizd = filter_item.filterizr({
      layout: 'packed',
  
    });
  }





  $('.portfolio-filter ul li').on('click', function () {
    $('.portfolio-filter ul li').removeClass('active');
    $(this).addClass('active');
  });


  var review = $('.review_slider');
  if (review.length) {
    review.owlCarousel({
      items: 1,
      loop: true,
      dots: true,
      autoplay: false,
      autoplayHoverPause: true,
      autoplayTimeout: 5000,
      nav: false,
      autoHeight: true,
      autoHeightClass: 'owl-height',
      animateOut: 'fadeOut',
      animateIn: 'fadeIn'
    });
  }

  // Testimonials Carousel
  var testimonialsCarousel = $('.testimonials-carousel');
  if (testimonialsCarousel.length) {
    testimonialsCarousel.owlCarousel({
      items: 3,
      loop: true,
      dots: true,
      nav: true,
      autoplay: true,
      autoplayHoverPause: true,
      autoplayTimeout: 4000,
      autoplaySpeed: 800,
      navText: ['<i class="ti-angle-left"></i>', '<i class="ti-angle-right"></i>'],
      responsive: {
        0: {
          items: 1,
          margin: 20,
          nav: false
        },
        576: {
          items: 1,
          margin: 30,
          nav: false
        },
        768: {
          items: 2,
          margin: 30,
          nav: true
        },
        992: {
          items: 3,
          margin: 30,
          nav: true
        }
      },
      onInitialized: function() {
        // Ensure equal height for all testimonial cards
        setTimeout(function() {
          var maxHeight = 0;
          $('.testimonials-carousel .testimonial-card').each(function() {
            var height = $(this).outerHeight();
            if (height > maxHeight) {
              maxHeight = height;
            }
          });
          $('.testimonials-carousel .testimonial-card').css('height', maxHeight + 'px');
        }, 100);
      }
    });
  }

  // Portfolio Categories Tabs functionality
  $('#portfolioTabs a[data-toggle="tab"]').on('click', function (e) {
    e.preventDefault();
    
    // Remove active class from all tabs and tab panes
    $('#portfolioTabs .nav-link').removeClass('active');
    $('.tab-pane').removeClass('show active');
    
    // Add active class to clicked tab
    $(this).addClass('active');
    
    // Show corresponding tab pane
    var target = $(this).attr('href');
    $(target).addClass('show active');
  });

  // Initialize portfolio gallery with proper event handling
  $(document).ready(function() {
    if ($.fn.magnificPopup) {
      $('.portfolio_gallery').magnificPopup({
        delegate: '.gallery-popup',
        type: 'image',
        gallery: {
          enabled: true,
          navigateByImgClick: true,
          preload: [0,1]
        },
        zoom: {
          enabled: true,
          duration: 300
        },
        image: {
          titleSrc: function(item) {
            return item.el.find('img').attr('alt');
          },
          verticalFit: true
        },
        callbacks: {
          beforeOpen: function() {
            // Ensure the popup opens correctly
            this.st.mainClass = 'mfp-zoom-in';
          }
        }
      });
    }
  });

  // Testimonial Read More Functionality
  $(document).ready(function() {
    // Check if text needs read more button
    $('.testimonial-text').each(function() {
      var $text = $(this);
      var $container = $text.closest('.testimonial-text-container');
      var $button = $container.find('.read-more-btn');
      
      // Check if text is truncated (more than 4 lines)
      if ($text[0].scrollHeight > $text[0].clientHeight) {
        $button.show();
      } else {
        $button.hide();
      }
    });

    // Read More button click handler
    $(document).on('click', '.read-more-btn', function() {
      var $button = $(this);
      var $container = $button.closest('.testimonial-text-container');
      var $text = $container.find('.testimonial-text');
      
      if ($text.hasClass('expanded')) {
        // Collapse text
        $text.removeClass('expanded');
        $button.text('Read More');
        $text.css('display', '-webkit-box');
      } else {
        // Expand text
        $text.addClass('expanded');
        $button.text('Read Less');
        $text.css('display', 'block');
      }
      
      // Recalculate carousel heights after text expansion/collapse
      if (testimonialsCarousel.length) {
        setTimeout(function() {
          var maxHeight = 0;
          $('.testimonials-carousel .testimonial-card').each(function() {
            var height = $(this).outerHeight();
            if (height > maxHeight) {
              maxHeight = height;
            }
          });
          $('.testimonials-carousel .testimonial-card').css('height', maxHeight + 'px');
        }, 300);
      }
    });
  });

}(jQuery));