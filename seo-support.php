<?php
/*
Plugin Name: SeoSupport
Description: Plugin dodający elementy zoptymalizowane pod SEO, jak sitemap.xml.
Version: 1.1
Author: IT Assistance Stockholm
*/

defined('ABSPATH') or die('No script kiddies please!');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/sitemap-generator.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/robots-editor.php';
require_once plugin_dir_path(__FILE__) . 'includes/meta-fields.php';
require_once __DIR__ . '/includes/indexnow.php';

// Hook to create sitemap on plugin activation

register_activation_hook(__FILE__, 'seosupport_generate_sitemap');




// Register schedulde (ones per day)
if (!wp_next_scheduled('seosupport_daily_sitemap')) {
    wp_schedule_event(time(), 'daily', 'seosupport_daily_sitemap');
}

// Podpięcie do cron hooka
add_action('seosupport_daily_sitemap', 'seosupport_generate_sitemap');

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('seosupport_daily_sitemap');
});


add_action('wp_head', function () {
    if (is_singular()) {
        global $post;

        $seo_title = get_post_meta($post->ID, '_seo_support_title', true);
        $seo_desc = get_post_meta($post->ID, '_seo_support_description', true);

        if ($seo_title) {
            echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
        }

        if ($seo_desc) {
            echo '<meta name="description" content="' . esc_attr($seo_desc) . '">' . "\n";
        }
    }
});


