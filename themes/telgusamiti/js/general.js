/**
 * Telugu Samiti Theme - General JavaScript
 * Menu functionality and other common scripts
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    
    /**
     * Main Menu Functionality
     */
    initMainMenu();

    /**
     * Initialize Main Menu
     */
    function initMainMenu() {
      var $mainMenu = $('#main-menu');
      var $menuToggle = $('.menu-toggle');
      
      // Create mobile menu toggle button if it doesn't exist
      if ($menuToggle.length === 0) {
        $menuToggle = $('<button class="menu-toggle" aria-label="Toggle menu" aria-expanded="false"><span class="menu-toggle-icon"></span></button>');
        $('#site-navigation').prepend($menuToggle);
      }

      // Toggle mobile menu
      $menuToggle.on('click', function(e) {
        e.preventDefault();
        var isExpanded = $(this).attr('aria-expanded') === 'true';
        
        $(this).attr('aria-expanded', !isExpanded);
        $mainMenu.toggleClass('menu-open');
        $('body').toggleClass('menu-open');
        $(this).toggleClass('active');
      });

      // Handle menu items with submenus (mobile only)
      $mainMenu.find('.menu-item-has-children > a').on('click', function(e) {
        // Only prevent default on mobile devices
        if ($(window).width() < 992) {
          e.preventDefault();
          e.stopPropagation();
          $(this).parent().toggleClass('submenu-open');
          $(this).siblings('.sub-menu').slideToggle(300);
        }
        // On desktop, allow normal link behavior
      });
      
      // Desktop hover enhancement (optional - CSS handles it, but this ensures it works)
      if ($(window).width() >= 992) {
        $mainMenu.find('.menu-item-has-children').on('mouseenter', function() {
          $(this).find('.sub-menu').stop(true, true).fadeIn(200);
        }).on('mouseleave', function() {
          $(this).find('.sub-menu').stop(true, true).fadeOut(200);
        });
      }

      // Close menu when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest('#site-navigation').length && $mainMenu.hasClass('menu-open')) {
          $mainMenu.removeClass('menu-open');
          $('body').removeClass('menu-open');
          $menuToggle.attr('aria-expanded', 'false').removeClass('active');
        }
      });

      // Close menu on window resize (if mobile menu is open)
      $(window).on('resize', function() {
        var windowWidth = $(window).width();
        if (windowWidth >= 992) {
          $mainMenu.removeClass('menu-open');
          $('body').removeClass('menu-open');
          $menuToggle.attr('aria-expanded', 'false').removeClass('active');
          // Show submenus on desktop (CSS will handle hover)
          $mainMenu.find('.sub-menu').css('display', '');
          $mainMenu.find('.menu-item-has-children').removeClass('submenu-open');
          
          // Re-initialize desktop hover
          $mainMenu.find('.menu-item-has-children').off('mouseenter mouseleave').on('mouseenter', function() {
            $(this).find('.sub-menu').stop(true, true).fadeIn(200);
          }).on('mouseleave', function() {
            $(this).find('.sub-menu').stop(true, true).fadeOut(200);
          });
        } else {
          // Hide submenus on mobile
          $mainMenu.find('.sub-menu').hide();
          $mainMenu.find('.menu-item-has-children').removeClass('submenu-open');
          // Remove desktop hover handlers
          $mainMenu.find('.menu-item-has-children').off('mouseenter mouseleave');
        }
      });

      // Smooth scroll for anchor links
      $mainMenu.find('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
          e.preventDefault();
          $('html, body').animate({
            scrollTop: target.offset().top - 90 // Account for header height
          }, 600);
          
          // Close mobile menu if open
          if ($mainMenu.hasClass('menu-open')) {
            $mainMenu.removeClass('menu-open');
            $('body').removeClass('menu-open');
            $menuToggle.attr('aria-expanded', 'false').removeClass('active');
          }
        }
      });

      // Add active class on scroll for anchor links
      if ($('a[href^="#"]').length) {
        $(window).on('scroll', function() {
          var scrollPos = $(window).scrollTop() + 100;
          
          $mainMenu.find('a[href^="#"]').each(function() {
            var currLink = $(this);
            var refElement = $(currLink.attr('href'));
            
            if (refElement.length && refElement.position().top <= scrollPos && refElement.position().top + refElement.height() > scrollPos) {
              $mainMenu.find('a').removeClass('active');
              currLink.addClass('active');
            }
          });
        });
      }

      // Highlight current menu item
      var currentPath = window.location.pathname;
      $mainMenu.find('a').each(function() {
        var linkPath = $(this).attr('href');
        if (linkPath && (currentPath === linkPath || currentPath.indexOf(linkPath) === 0)) {
          $(this).addClass('current-menu-item-link');
          $(this).parent().addClass('current-menu-item');
        }
      });
    }

    /**
     * Sticky Header Enhancement
     */
    var $header = $('#header');
    var headerHeight = $header.outerHeight();
    
    $(window).on('scroll', function() {
      if ($(window).scrollTop() > 50) {
        $header.addClass('scrolled');
      } else {
        $header.removeClass('scrolled');
      }
    });

  });

})(jQuery);

