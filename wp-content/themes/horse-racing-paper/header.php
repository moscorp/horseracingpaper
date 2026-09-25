<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('hrp-theme'); ?>>
<?php wp_body_open(); ?>
<header class="hrp-shell">
    <p class="hrp-brand"><?php bloginfo('name'); ?></p>
    <?php if (get_bloginfo('description')) : ?>
        <p class="hrp-tagline"><?php bloginfo('description'); ?></p>
    <?php endif; ?>
</header>
