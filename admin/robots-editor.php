<?php

// Hook this section into your admin page manually from settings-page.php
function seosupport_render_robots_editor() {
    $robots_path = ABSPATH . 'robots.txt';
    $robots_content = file_exists($robots_path) ? file_get_contents($robots_path) : '';

    // Handle form actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
        // Save robots.txt
        if (isset($_POST['seosupport_save_robots']) && isset($_POST['seosupport_robots_content'])) {
            $new_content = sanitize_textarea_field($_POST['seosupport_robots_content']);
            file_put_contents($robots_path, $new_content);
            echo '<div class="updated"><p><strong>robots.txt has been saved.</strong></p></div>';
            $robots_content = $new_content;
        }

        // Reset to default
        if (isset($_POST['seosupport_reset_robots'])) {
            $default_content = "User-agent: *\nDisallow:\n";
            if (file_exists(ABSPATH . 'sitemap.xml')) {
                $default_content .= "Sitemap: " . home_url('/sitemap.xml') . "\n";
            }
            file_put_contents($robots_path, $default_content);
            echo '<div class="updated"><p><strong>robots.txt has been reset to default.</strong></p></div>';
            $robots_content = $default_content;
        }
    }

    ?>
    <h2>Edit robots.txt</h2>
    <form method="post">
        <textarea name="seosupport_robots_content" rows="10" style="width:100%;"><?php echo esc_textarea($robots_content); ?></textarea>
        <?php submit_button('Save robots.txt', 'primary', 'seosupport_save_robots'); ?>
        <?php submit_button('Reset to default', 'secondary', 'seosupport_reset_robots'); ?>
    </form>
    <?php
}

// Optional: call this function at the end of your settings page
// seosupport_render_robots_editor();
