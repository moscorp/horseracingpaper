<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('hrp-theme'); ?>>
<?php wp_body_open(); ?>
<header class="hrp-shell border-b border-ink-line">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="hrp-brand-lockup py-2">
        <img class="hrp-brand-mark" src="<?php echo esc_url(hrp_logo_url()); ?>" alt="<?php echo esc_attr(hrp_brand_title()); ?>">
        <span>
            <span class="hrp-brand-title block"><?php echo esc_html(hrp_brand_title()); ?></span>
            <span class="hrp-brand-sub block"><?php echo esc_html(hrp_brand_subtitle()); ?></span>
        </span>
    </a>
</header>
