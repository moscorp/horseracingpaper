<footer class="hrp-footer">
    <div class="hrp-footer-inner">
        <div>
            <span class="font-display text-brass"><?php echo esc_html(hrp_brand_title()); ?></span>
            <span class="mx-1">·</span>
            <span><?php echo esc_html(hrp_brand_subtitle()); ?></span>
        </div>
        <nav class="flex flex-wrap gap-x-4 gap-y-1" aria-label="頁腳">
            <a href="<?php echo esc_url(hrp_page_url('about')); ?>">關於我們</a>
            <a href="<?php echo esc_url(hrp_page_url('privacy')); ?>">私隱政策</a>
            <a href="<?php echo esc_url(hrp_page_url('tos')); ?>">使用條款</a>
        </nav>
        <div>© <?php echo esc_html(gmdate('Y')); ?></div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
