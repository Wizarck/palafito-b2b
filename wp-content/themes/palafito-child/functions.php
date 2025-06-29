<?php
// Encola el CSS del tema hijo, dependiendo del tema padre Kadence
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('palafito-child-style', get_stylesheet_uri(), ['kadence-style'], '1.0');
});