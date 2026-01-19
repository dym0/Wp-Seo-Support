<?php
// Hook into post edit screen to add meta box
add_action('add_meta_boxes', function () {
    $post_types = get_post_types(['public' => true], 'names');

    foreach ($post_types as $post_type) {
        add_meta_box(
            'seo_meta_fields',
            'SEO Settings',
            'seo_support_render_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
});

// Render the meta box fields
function seo_support_render_meta_box($post) {
    $seo_title = get_post_meta($post->ID, '_seo_support_title', true);
    $seo_desc = get_post_meta($post->ID, '_seo_support_description', true);
    $seo_slug = get_post_meta($post->ID, '_seo_support_slug', true);

    wp_nonce_field('seo_support_save_meta', 'seo_support_nonce');


    // SEO Title
    echo '<p><label for="seo_support_title">SEO Title</label></p>';
    echo '<input type="text" id="seo_support_title" name="seo_support_title" value="' . esc_attr($seo_title) . '" style="width:100%;" maxlength="70" />';
    echo '<p id="seo_title_counter"></p>';

    // SEO Description
    echo '<p><label for="seo_support_description">Meta Description</label></p>';
    echo '<textarea id="seo_support_description" name="seo_support_description" rows="3" style="width:100%;" maxlength="160">' . esc_textarea($seo_desc) . '</textarea>';
    echo '<p id="seo_description_counter"></p>';

    // SEO Slug
    echo '<p><label for="seo_support_slug">Custom Slug (optional)</label></p>';
    echo '<input type="text" id="seo_support_slug" name="seo_support_slug" value="' . esc_attr($seo_slug) . '" style="width:100%;" />';
    $permalink_base = get_permalink($post->ID);
    $parsed_url = wp_parse_url($permalink_base);
    $base_path = trailingslashit($parsed_url['scheme'] . '://' . $parsed_url['host'] . ($parsed_url['path'] ?? ''));

    echo '<p><label for="seo_support_slug">Custom Slug (optional)</label></p>';
    echo '<input type="text" id="seo_support_slug" name="seo_support_slug" value="' . esc_attr($current_slug) . '" style="width:100%;" />';
    echo '<p>Permalink: <span id="seo_slug_preview">' . esc_url($base_path . $current_slug) . '</span></p>';
    }

// Save meta fields when post is saved
add_action('save_post', function ($post_id) {
    if (!isset($_POST['seo_support_nonce']) || !wp_verify_nonce($_POST['seo_support_nonce'], 'seo_support_save_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['seo_support_title'])) {
        update_post_meta($post_id, '_seo_support_title', sanitize_text_field($_POST['seo_support_title']));
    }

    if (isset($_POST['seo_support_description'])) {
        update_post_meta($post_id, '_seo_support_description', sanitize_textarea_field($_POST['seo_support_description']));
    }
});


add_action('save_post', function ($post_id) {
    if (!isset($_POST['seo_support_nonce']) || !wp_verify_nonce($_POST['seo_support_nonce'], 'seo_support_save_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['seo_support_title'])) {
        update_post_meta($post_id, '_seo_support_title', sanitize_text_field($_POST['seo_support_title']));
    }

    if (isset($_POST['seo_support_description'])) {
        update_post_meta($post_id, '_seo_support_description', sanitize_textarea_field($_POST['seo_support_description']));
    }

    if (isset($_POST['seo_support_slug'])) {
        $slug = sanitize_title($_POST['seo_support_slug']);
        if (!empty($slug)) {
            wp_update_post([
                'ID' => $post_id,
                'post_name' => $slug
            ]);
        }
    }
});