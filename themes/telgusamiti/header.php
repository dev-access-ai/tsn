<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="profile" href="https://gmpg.org/xfn/11">
  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'telugusmiti'); ?></a>

    <!-- Inline SVG for clip-path (hidden, used by CSS) - Responsive -->
    <svg width="0" height="0" style="position: absolute;" aria-hidden="true">
      <defs>
        <clipPath id="innerClip" clipPathUnits="objectBoundingBox">
          <path d="M0.5 0C0.5 0 0.5336 0.0596 0.6218 0.0596C0.7093 0.0596 0.8242 0.0714 0.8286 0.1714C0.9284 0.1755 0.9394 0.2905 0.9394 0.3777C0.9394 0.4664 1 0.5 1 0.5C1 0.5 0.9394 0.5336 0.9394 0.6218C0.9394 0.7093 0.9284 0.8242 0.8286 0.8286C0.8242 0.9284 0.7093 0.9394 0.6218 0.9394C0.5336 0.9394 0.5 1 0.5 1C0.5 1 0.4664 0.9394 0.3777 0.9394C0.2905 0.9394 0.1755 0.9284 0.1714 0.8286C0.0714 0.8242 0.0596 0.7093 0.0596 0.6218C0.0596 0.5336 0 0.5 0 0.5C0 0.5 0.0596 0.4664 0.0596 0.3777C0.0596 0.2905 0.0714 0.1755 0.1714 0.1714C0.1755 0.0714 0.2905 0.0596 0.3777 0.0596C0.4664 0.0596 0.5 0 0.5 0Z"/>
        </clipPath>
      </defs>
    </svg>


    <header id="header">
      <div class="container">
        <a href="<?php echo esc_url( get_site_url() ); ?>" id="logo">
          <span class="circles">Circle</span>
          <img src="<?php echo get_template_directory_uri(); ?>/images/logo.png" alt="Telugu Samiti">
          <span class="circles">Circle</span>
        </a>
        <div class="right-navigation">
          <nav id="site-navigation" class="main-navigation">
            <?php
              wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_id' => 'main-menu',
                'fallback_cb' => false,
              )); 
            ?>
          </nav>
          <?php
            // Display user navigation (login/register or profile/dashboard/logout)
            if (shortcode_exists('tsn_user_nav')) {
              echo do_shortcode('[tsn_user_nav]');
            }
          ?>
        </div>
      </div>
    </header>