<?php
if (!defined('ABSPATH')) exit;

/**
 * POST META (SEO title / description / slug)
 */

// Add meta box for public post types
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
    $seo_desc  = get_post_meta($post->ID, '_seo_support_description', true);
    $seo_slug  = get_post_meta($post->ID, '_seo_support_slug', true);

    // current actual slug
    $current_slug = $post->post_name;

    wp_nonce_field('seo_support_save_meta', 'seo_support_nonce');

    // SEO Title
    echo '<p><label for="seo_support_title">SEO Title</label></p>';
    echo '<input type="text" id="seo_support_title" name="seo_support_title" value="' . esc_attr($seo_title) . '" style="width:100%;" maxlength="70" />';
    echo '<p id="seo_title_counter"></p>';

    // SEO Description
    echo '<p><label for="seo_support_description">Meta Description</label></p>';
    echo '<textarea id="seo_support_description" name="seo_support_description" rows="3" style="width:100%;" maxlength="160">' . esc_textarea($seo_desc) . '</textarea>';
    echo '<p id="seo_description_counter"></p>';

    // Custom Slug
    echo '<p><label for="seo_support_slug">Custom Slug (optional)</label></p>';
    echo '<input type="text" id="seo_support_slug" name="seo_support_slug" value="' . esc_attr($seo_slug) . '" style="width:100%;" />';

    // Permalink preview
    $permalink_base = get_permalink($post->ID);
    if ($permalink_base) {
        $parsed_url = wp_parse_url($permalink_base);
        $base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';

        // Replace current slug in URL with preview slug
        $preview_slug = $seo_slug ? $seo_slug : $current_slug;

        // best-effort: show base + path (without query)
        echo '<p>Permalink preview: <span id="seo_slug_preview">' . esc_url($base . rtrim(dirname($path), '/') . '/' . $preview_slug . '/') . '</span></p>';
    }
}

// Save meta fields when post is saved
add_action('save_post', function ($post_id) {
    if (!isset($_POST['seo_support_nonce']) || !wp_verify_nonce($_POST['seo_support_nonce'], 'seo_support_save_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['seo_support_title'])) {
        update_post_meta($post_id, '_seo_support_title', sanitize_text_field(wp_unslash($_POST['seo_support_title'])));
    }

    if (isset($_POST['seo_support_description'])) {
        update_post_meta($post_id, '_seo_support_description', sanitize_textarea_field(wp_unslash($_POST['seo_support_description'])));
    }

    if (isset($_POST['seo_support_slug'])) {
        $slug = sanitize_title(wp_unslash($_POST['seo_support_slug']));
        update_post_meta($post_id, '_seo_support_slug', $slug);

        if (!empty($slug)) {
            // update actual post slug
            wp_update_post([
                'ID' => $post_id,
                'post_name' => $slug
            ]);
        }
    }
});


/**
 * TERM META (WooCommerce product categories: product_cat)
 * Adds SEO Title + Meta Description fields for product categories.
 */

add_action('product_cat_add_form_fields', function () {
    wp_nonce_field('seo_support_save_product_cat', 'seo_support_product_cat_nonce');
    ?>
    <div class="form-field term-group">
        <label for="seo_support_term_title">SEO Title</label>
        <input type="text" id="seo_support_term_title" name="seo_support_term_title" value="" maxlength="70" />
        <p class="description">Mea title for product category</p>
    </div>

    <div class="form-field term-group">
        <label for="seo_support_term_description">Meta Description</label>
        <textarea id="seo_support_term_description" name="seo_support_term_description" rows="5" cols="40" maxlength="160"></textarea>
        <p class="description">Meta description for product category.</p>
    </div>
    <?php
});

add_action('product_cat_edit_form_fields', function ($term) {
    $title = get_term_meta($term->term_id, '_seo_support_term_title', true);
    $desc  = get_term_meta($term->term_id, '_seo_support_term_description', true);

    wp_nonce_field('seo_support_save_product_cat', 'seo_support_product_cat_nonce');
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="seo_support_term_title">SEO Title</label></th>
        <td>
            <input type="text" id="seo_support_term_title" name="seo_support_term_title" value="<?php echo esc_attr($title); ?>" class="regular-text" maxlength="70" />
            <p class="description">Title </p>
        </td>
    </tr>

    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="seo_support_term_description">Meta Description</label></th>
        <td>
            <textarea id="seo_support_term_description" name="seo_support_term_description" rows="5" class="large-text" maxlength="160"><?php echo esc_textarea($desc); ?></textarea>
            <p class="description">Meta description </p>
        </td>
    </tr>
    <?php
}, 10, 1);

add_action('created_product_cat', 'seo_support_save_product_cat_meta', 10, 2);
add_action('edited_product_cat',  'seo_support_save_product_cat_meta', 10, 2);

function seo_support_save_product_cat_meta($term_id, $tt_id) {
    if (!isset($_POST['seo_support_product_cat_nonce']) ||
        !wp_verify_nonce($_POST['seo_support_product_cat_nonce'], 'seo_support_save_product_cat')) {
        return;
    }

    if (!current_user_can('manage_product_terms')) {
        return;
    }

    $title = isset($_POST['seo_support_term_title'])
        ? sanitize_text_field(wp_unslash($_POST['seo_support_term_title']))
        : '';

    $desc = isset($_POST['seo_support_term_description'])
        ? sanitize_textarea_field(wp_unslash($_POST['seo_support_term_description']))
        : '';

    update_term_meta($term_id, '_seo_support_term_title', $title);
    update_term_meta($term_id, '_seo_support_term_description', $desc);
}

/**
 * Front-end output for product category pages
 */

// Override <title> on product category archive if custom title exists
add_filter('pre_get_document_title', function ($title) {
    if (!function_exists('is_tax') || !is_tax('product_cat')) return $title;

    $term = get_queried_object();
    if (!$term || empty($term->term_id)) return $title;

    $custom = get_term_meta($term->term_id, '_seo_support_term_title', true);
    if (is_string($custom) && $custom !== '') {
        return $custom;
    }

    return $title;
}, 20);

// Output meta description on product category pages if custom description exists
add_action('wp_head', function () {
    if (!function_exists('is_tax') || !is_tax('product_cat')) return;

    $term = get_queried_object();
    if (!$term || empty($term->term_id)) return;

    $desc = get_term_meta($term->term_id, '_seo_support_term_description', true);
    if (!is_string($desc) || $desc === '') return;

    echo "\n" . '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
}, 1);
