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