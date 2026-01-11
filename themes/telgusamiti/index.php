<?php

/**
 * The main template file
 *
 * @package TeluguSamiti
 */
get_header();
?>

<main id="main" class="site-main">
  <div class="container">
    <div class="content-area">
      <?php
      if (have_posts()):
        while (have_posts()):
          the_post();
          ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php
get_footer();
?>