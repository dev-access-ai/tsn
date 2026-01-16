<?php

/**
 * Telugu Samiti Theme Functions
 *
 * @package TeluguSamiti
 */
if (!defined('ABSPATH')) {
  exit;  // Exit if accessed directly.
}

/**
 * Theme Setup
 */
function telugusmiti_setup()
{
  // Add theme support for title tag
  add_theme_support('title-tag');

  // Add theme support for post thumbnails
  add_theme_support('post-thumbnails');

  // Add theme support for custom logo
  add_theme_support('custom-logo', array(
    'height' => 100,
    'width' => 400,
    'flex-height' => true,
    'flex-width' => true,
  ));

  // Add theme support for HTML5 markup
  add_theme_support('html5', array(
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
    'style',
    'script',
  ));

  // Add theme support for selective refresh for widgets
  add_theme_support('customize-selective-refresh-widgets');

  // Add theme support for post formats
  add_theme_support('post-formats', array(
    'aside',
    'image',
    'video',
    'quote',
    'link',
    'gallery',
    'audio',
  ));

  // Register navigation menus
  register_nav_menus(array(
    'primary' => __('Primary Menu', 'telugusmiti'),
    'footer' => __('Footer Menu', 'telugusmiti'),
  ));
}

add_action('after_setup_theme', 'telugusmiti_setup');

/**
 * Enqueue Scripts and Styles
 */
function telugusmiti_scripts()
{
  // Enqueue Google Fonts - Anton and Nunito
  wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Anton&family=Nunito:wght@200;300;400;500;600;700;800&display=swap', array(), null);

  // Enqueue Bootstrap CSS from CDN
  wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2');

  // Enqueue Bootstrap JS Bundle from CDN (includes Popper)
  wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array(), '5.3.2', true);

  // Enqueue stylesheet (after Bootstrap so custom styles can override)
  wp_enqueue_style('telugusmiti-style', get_stylesheet_uri(), array('bootstrap-css'), '1.0.0');

  // Enqueue custom CSS from css folder
  wp_enqueue_style('telugusmiti-custom-style', get_template_directory_uri() . '/css/style.css', array('telugusmiti-style'), '1.0.0');

  // Enqueue Responsive CSS
  wp_enqueue_style('telugusmiti-responsive', get_template_directory_uri() . '/css/responsive.css', array('telugusmiti-custom-style'), '1.0.0');

  // Enqueue Swiper CSS
  wp_enqueue_style('swiper-css', get_template_directory_uri() . '/css/swiper-bundle.min.css', array(), '11.2.10');
  
  // Enqueue User Navigation CSS
  wp_enqueue_style('tsn-user-nav', get_template_directory_uri() . '/css/user-nav.css', array('telugusmiti-custom-style'), '1.0.0');

  // Enqueue global overflow fix
  wp_enqueue_style('global-overflow-fix', get_template_directory_uri() . '/css/global-overflow-fix.css', array(), '1.0.0');
  
  // Enqueue jQuery (WordPress includes it, but we ensure it's loaded)
  wp_enqueue_script('jquery');
  
  // Enqueue Swiper JS
  wp_enqueue_script('swiper-js', get_template_directory_uri() . '/js/swiper-bundle.min.js', array(), '11.2.10', true);
  
  // Enqueue scripts
  wp_enqueue_script('telugusmiti-common', get_template_directory_uri() . '/js/general.js', array('jquery', 'bootstrap-js'), '1.0.0', true);
  
  // Enqueue User Navigation JS
  wp_enqueue_script('tsn-user-nav-js', get_template_directory_uri() . '/js/user-nav.js', array('jquery'), '1.0.2', true);
  
  // Localize User Navigation script for AJAX
  wp_localize_script('tsn-user-nav-js', 'tsnUserNav', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('tsn_membership_nonce')
  ));
  
  // Enqueue Banner Swiper initialization script (only on home page)
  if (is_page_template('template-home.php')) {
    wp_enqueue_script('banner-swiper', get_template_directory_uri() . '/js/banner-swiper.js', array('swiper-js'), '1.0.0', true);
    wp_enqueue_script('community-swiper', get_template_directory_uri() . '/js/community-swiper.js', array('swiper-js'), '1.0.0', true);
  }



  // Enqueue Membership Page CSS
  if (is_page('membership')) {
    wp_enqueue_style('tsn-membership-style', get_template_directory_uri() . '/css/membership.css', array('telugusmiti-custom-style'), '1.0.0');
  }

  // Enqueue Member Dashboard Page CSS
  if (is_page('member-dashboard') || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'member-dashboard') !== false)) {
    wp_enqueue_style('tsn-member-dashboard-style', get_template_directory_uri() . '/css/member-dashboard.css', array('telugusmiti-custom-style'), '1.0.0');
  }

  // Enqueue Donations Page CSS (only on donations page)
  if (is_page('donations') || is_page('donate') || (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/donations') !== false || strpos($_SERVER['REQUEST_URI'], '/donate') !== false))) {
    wp_enqueue_style('tsn-donations-style', get_template_directory_uri() . '/css/donations.css', array('telugusmiti-custom-style'), '1.0.0');
  }

  // Enqueue Events Page CSS (only on main /events listing page, not event detail pages)
  $event_slug = get_query_var('event_slug');
  // Only load events.css if we're on the events page AND there's no event slug (not a detail page)
  if (empty($event_slug)) {
    if (is_page('events') || (isset($_SERVER['REQUEST_URI']) && preg_match('#/events/?$#', $_SERVER['REQUEST_URI']) && !preg_match('#/events/[^/]+#', $_SERVER['REQUEST_URI']))) {
    wp_enqueue_style('tsn-events-style', get_template_directory_uri() . '/css/events.css', array('telugusmiti-custom-style'), '1.0.0');
    }
  }

  // Enqueue Event Detail Page CSS (only on /events/* pages, not the main /events page)
  if (isset($_SERVER['REQUEST_URI'])) {
    $request_uri = $_SERVER['REQUEST_URI'];
    // Check if URL matches /events/ followed by something (event slug)
    // This excludes the main /events page but includes /events/event-slug
    if (preg_match('#/events/[^/]+#', $request_uri)) {
      wp_enqueue_style('tsn-event-detail-style', get_template_directory_uri() . '/css/event-detail.css', array('telugusmiti-custom-style'), '1.0.0');
    }
  }

  // Enqueue comment reply script
  if (is_singular() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }
}

add_action('wp_enqueue_scripts', 'telugusmiti_scripts');

/**
 * Register Widget Areas
 */
function telugusmiti_widgets_init()
{
  register_sidebar(array(
    'name' => __('Sidebar', 'telugusmiti'),
    'id' => 'sidebar-1',
    'description' => __('Add widgets here.', 'telugusmiti'),
    'before_widget' => '<section id="%1$s" class="widget %2$s">',
    'after_widget' => '</section>',
    'before_title' => '<h2 class="widget-title">',
    'after_title' => '</h2>',
  ));

  register_sidebar(array(
    'name' => __('Footer 1', 'telugusmiti'),
    'id' => 'footer-1',
    'description' => __('Add widgets here to appear in your footer.', 'telugusmiti'),
    'before_widget' => '<section id="%1$s" class="widget %2$s">',
    'after_widget' => '</section>',
    'before_title' => '<h2 class="widget-title">',
    'after_title' => '</h2>',
  ));

  register_sidebar(array(
    'name' => __('Footer 2', 'telugusmiti'),
    'id' => 'footer-2',
    'description' => __('Add widgets here to appear in your footer.', 'telugusmiti'),
    'before_widget' => '<section id="%1$s" class="widget %2$s">',
    'after_widget' => '</section>',
    'before_title' => '<h2 class="widget-title">',
    'after_title' => '</h2>',
  ));
}

add_action('widgets_init', 'telugusmiti_widgets_init');

/**
 * Custom Excerpt Length
 */
function telugusmiti_excerpt_length($length) {
  return 30;
}

add_filter('excerpt_length', 'telugusmiti_excerpt_length');

/**
 * Custom Excerpt More
 */
function telugusmiti_excerpt_more($more) {
  return '...';
}

add_filter('excerpt_more', 'telugusmiti_excerpt_more');

/**
 * Add custom body classes
 */
function telugusmiti_body_classes($classes) {
  if (!is_active_sidebar('sidebar-1')) {
    $classes[] = 'no-sidebar';
  }
  return $classes;
}

add_filter('body_class', 'telugusmiti_body_classes');

/**
 * WordPress Breadcrumb Function
 * Displays breadcrumb navigation using WordPress functions
 */
function telugusmiti_breadcrumb() {
  // Don't show breadcrumb on front page
  if (is_front_page()) {
    return;
  }

  $home_url = esc_url(home_url('/'));
  $home_title = get_bloginfo('name');

  echo '<nav aria-label="breadcrumb">';
  echo '<ol class="breadcrumb">';
  
  // Home link
  echo '<li class="breadcrumb-item">';
  echo '<a href="' . $home_url . '">';
  echo '<span class="mdi mdi-home-variant-outline"></span>';
  echo '</a>';
  echo '</li>';

  // Check if this is an event detail page (custom route)
  $event_slug = get_query_var('event_slug');
  if (!empty($event_slug)) {
    // Get event data
    global $wpdb;
    $event = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}tsn_events WHERE slug = %s AND status = 'published'",
      $event_slug
    ));
    
    if ($event) {
      // Events page link
      $events_page_url = home_url('/events');
      echo '<li class="breadcrumb-item">';
      echo '<a href="' . esc_url($events_page_url) . '">EVENTS</a>';
      echo '</li>';
      
      // Event title
      echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html($event->title) . '</li>';
    } else {
      // Fallback if event not found
      echo '<li class="breadcrumb-item active" aria-current="page">EVENTS</li>';
    }
  }
  // Check if this is the main events listing page
  elseif (is_page('events') || (isset($_SERVER['REQUEST_URI']) && preg_match('#^/.*?/events/?$#', $_SERVER['REQUEST_URI']))) {
    echo '<li class="breadcrumb-item active" aria-current="page">EVENTS</li>';
  }

  // Current page/post
  elseif (is_page() || is_single() || is_singular()) {
    // Get the current post/page
    $post_id = get_the_ID();
    
    // For pages, check if it has a parent
    if (is_page() && $post_id) {
      $ancestors = get_post_ancestors($post_id);
      
      // Display parent pages if they exist
      if (!empty($ancestors)) {
        // Reverse the array to get ancestors in correct order (top to bottom)
        $ancestors = array_reverse($ancestors);
        
        foreach ($ancestors as $ancestor_id) {
          $ancestor_title = get_the_title($ancestor_id);
          $ancestor_url = get_permalink($ancestor_id);
          echo '<li class="breadcrumb-item">';
          echo '<a href="' . esc_url($ancestor_url) . '">' . esc_html($ancestor_title) . '</a>';
          echo '</li>';
        }
      }
    }
    
    // For single posts, show category if available
    if (is_single()) {
      $categories = get_the_category();
      if (!empty($categories)) {
        $category = $categories[0];
        echo '<li class="breadcrumb-item">';
        echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
        echo '</li>';
      }
    }
    
    // Display current page/post title
    $title = get_the_title();
    if (!empty($title)) {
      echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html($title) . '</li>';
    }
  } elseif (is_category()) {
    $category = get_queried_object();
    echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html($category->name) . '</li>';
  } elseif (is_tag()) {
    $tag = get_queried_object();
    echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html($tag->name) . '</li>';
  } elseif (is_archive()) {
    echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html(get_the_archive_title()) . '</li>';
  } elseif (is_search()) {
    echo '<li class="breadcrumb-item active" aria-current="page">Search Results</li>';
  } elseif (is_404()) {
    echo '<li class="breadcrumb-item active" aria-current="page">404 - Page Not Found</li>';
  }

  echo '</ol>';
  echo '</nav>';
}


function add_membership_input_classes() {
  if (is_page('membership') || is_page_template('template-membership.php')) {
    ?>
    <script>
      jQuery(document).ready(function($) {
        var $inputs = $('#tsn-membership-form input[type="text"], #tsn-membership-form input[type="email"], #tsn-membership-form input[type="tel"], #tsn-membership-form textarea');
        $inputs.addClass('form-control');

        var $selects = $('#tsn-membership-form select');
        $selects.addClass('form-select');
        $inputs.each(function() {
          var $this = $(this);
          var $label = $('label[for="' + $this.attr('id') + '"]');
          
          // Wrap in form-group with unique class
          var name = $this.attr('name');
          var className = 'form-group';
          if (name) {
            className += ' form-group-' + name.replace(/[^a-zA-Z0-9-_]/g, '-');
          }
          $this.wrap('<div class="' + className + '"></div>');

          if (!$this.attr('placeholder')) {
            var placeholderText = $this.attr('name');
            if ($label.length > 0) {
              placeholderText = $label.text();
            }
            if (placeholderText) {
              $this.attr('placeholder', placeholderText.replace(/[:*]/g, '').trim());
            }
          }
          
          if ($label.length > 0) {
            $label.hide();
          }
        });

        $selects.each(function() {
          var $this = $(this);
          var $label = $('label[for="' + $this.attr('id') + '"]');
          
          // Wrap in form-group with unique class
          var name = $this.attr('name');
          var className = 'form-group';
          if (name) {
            className += ' form-group-' + name.replace(/[^a-zA-Z0-9-_]/g, '-');
          }
          $this.wrap('<div class="' + className + '"></div>');

          if (!$this.attr('placeholder')) {
            var placeholderText = $this.attr('name');
            if ($label.length > 0) {
              placeholderText = $label.text();
            }
            if (placeholderText) {
              $this.attr('placeholder', placeholderText.replace(/[:*]/g, '').trim());
            }
          }
          
          if ($label.length > 0) {
            $label.hide();
          }
        });
      });
    </script>
    <?php
  }
}
add_action('wp_footer', 'add_membership_input_classes');
