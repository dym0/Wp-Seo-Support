<?php

function seosupport_add_admin_menu() {
    add_menu_page(
        'SeoSupport',
        'SeoSupport',
        'manage_options',
        'seosupport',
        'seosupport_settings_page',
        'dashicons-chart-line'
    );
}
add_action('admin_menu', 'seosupport_add_admin_menu');

function seosupport_settings_page() {
    if ( ! current_user_can('manage_options') ) {
        return;
    }

    // --- Zakładki ---
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

    // Jeśli jesteśmy na zakładce IndexNow – obsłuż POST najpierw.
    if ( $current_tab === 'indexnow' && function_exists('seo_support_indexnow_handle_post_on_seosupport_page') ) {
        seo_support_indexnow_handle_post_on_seosupport_page();
    }

    // Dane do zakładki Sitemap (Twoje oryginalne)
    $sitemap_url   = home_url('/sitemap.xml');
    $generated     = isset($_GET['generated']);
    $last_generated = get_option('seosupport_last_generated');
    ?>
    <div class="wrap">
        <h1>SeoSupport - Settings</h1>

        <h2 class="nav-tab-wrapper" style="margin-top: 12px;">
            <a href="<?php echo esc_url( admin_url('admin.php?page=seosupport&tab=general') ); ?>"
               class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                Sitemap
            </a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=seosupport&tab=indexnow') ); ?>"
               class="nav-tab <?php echo $current_tab === 'indexnow' ? 'nav-tab-active' : ''; ?>">
                IndexNow
            </a>
        </h2>

        <?php
        // ---------------- Zakładka: Sitemap (General) ----------------
        if ( $current_tab === 'general' ) :
        ?>
            <?php if ($last_generated): ?>
                <p><strong>Last sitemap update:</strong> <?php echo esc_html($last_generated); ?></p>
            <?php else: ?>
                <p><strong>Sitemap not genereated yet.</strong></p>
            <?php endif; ?>

            <?php if ($generated): ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><strong>Sitemap was genereted succesfully.</strong></p>
                </div>
            <?php endif; ?>

            <p>Main sitemap:</p>
            <input type="text" value="<?php echo esc_url($sitemap_url); ?>" readonly style="width:100%;">

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="seosupport_generate_sitemap">
                <?php submit_button('Wygeneruj sitemapę teraz'); ?>
            </form>

            <?php
            // Twój edytor robots.txt
            if ( function_exists('seosupport_render_robots_editor') ) {
                seosupport_render_robots_editor();
            }
            ?>

        <?php
        // ---------------- Zakładka: IndexNow ----------------
        elseif ( $current_tab === 'indexnow' ) :

            // Renderujemy pełną sekcję IndexNow
            if ( function_exists('seo_support_indexnow_render_tab_content') ) {
                seo_support_indexnow_render_tab_content();
            } else {
                echo '<p>IndexNow module is not loaded. Make sure you required includes/indexnow.php.</p>';
            }

        endif; // koniec przełączania tabów
        ?>
    </div>
    <?php
}

// --- Akcja generowania sitemap (Twoje oryginalne) ---
add_action('admin_post_seosupport_generate_sitemap', 'seosupport_handle_generate_sitemap');

function seosupport_handle_generate_sitemap() {
    if (!current_user_can('manage_options')) {
        wp_die('Brak uprawnień');
    }

    seosupport_generate_sitemap();
    wp_redirect(admin_url('admin.php?page=seosupport&generated=true'));
    exit;
}

// --- Skrypt liczników meta (Twoje oryginalne) ---
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && in_array($screen->base, ['post', 'page'])) {
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('seo_support_title');
    const titleCounter = document.getElementById('seo_title_counter');
    const descInput = document.getElementById('seo_support_description');
    const descCounter = document.getElementById('seo_description_counter');

    function updateCounters() {
        if (titleInput && titleCounter) {
            const len = titleInput.value.length;
            titleCounter.innerText = len + " characters";
            titleCounter.style.color = (len > 70) ? 'red' : 'green';
        }

        if (descInput && descCounter) {
            const len = descInput.value.length;
            descCounter.innerText = len + " characters";
            descCounter.style.color = (len > 160) ? 'red' : 'green';
        }
    }

    if (titleInput) titleInput.addEventListener('input', updateCounters);
    if (descInput) descInput.addEventListener('input', updateCounters);
    updateCounters();
});
</script>
<?php
    }
});