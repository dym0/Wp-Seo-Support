<?php
/**
 * SeoSupport – IndexNow module
 */

if (!defined('ABSPATH')) { exit; }

// ---- Constants ---------------------------------------------------------------
const SEO_SUPPORT_INDEXNOW_ENABLED_OPT    = 'seo_support_indexnow_enabled';
const SEO_SUPPORT_INDEXNOW_KEY_OPT        = 'seo_support_indexnow_key';
const SEO_SUPPORT_INDEXNOW_ENDPOINTS_OPT  = 'seo_support_indexnow_endpoints';
const SEO_SUPPORT_INDEXNOW_POSTTYPES_OPT  = 'seo_support_indexnow_post_types';
const SEO_SUPPORT_INDEXNOW_EVENTS_OPT     = 'seo_support_indexnow_events';
const SEO_SUPPORT_INDEXNOW_LOG_OPT        = 'seo_support_indexnow_log';

// Default endpoints
function seo_support_indexnow_default_endpoints() {
    return [
        'bing'   => [ 'label' => 'Bing',   'endpoint' => 'https://www.bing.com/indexnow',  'enabled' => true ],
        'yandex' => [ 'label' => 'Yandex', 'endpoint' => 'https://yandex.com/indexnow',    'enabled' => false ],
    ];
}

// ---- Utilities ---------------------------------------------------------------
function seo_support_indexnow_generate_key($length = 32) {
    $bytes = random_bytes((int) ceil($length / 2));
    return substr(bin2hex($bytes), 0, $length);
}

function seo_support_indexnow_sanitize_key($key) {
    $key = trim((string) $key);
    $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    return substr($key, 0, 64);
}

function seo_support_indexnow_get_key_location_url($key) {
    $path = '/' . ltrim($key, '/') . '.txt';
    return home_url($path);
}

function seo_support_indexnow_write_key_file($key) {
    $key = seo_support_indexnow_sanitize_key($key);
    if (!$key) return false;

    $filepath = ABSPATH . $key . '.txt';

    if (file_exists($filepath)) {
        $contents = @file_get_contents($filepath);
        if ($contents === $key) {
            return true;
        }
    }

    if (is_writable(ABSPATH)) {
        $written = @file_put_contents($filepath, $key, LOCK_EX);
        return $written !== false;
    }

    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    global $wp_filesystem;
    if (!WP_Filesystem()) {
        return false;
    }
    return $wp_filesystem->put_contents($filepath, $key, FS_CHMOD_FILE);
}

function seo_support_indexnow_get_enabled_endpoints() {
    $saved = get_option(SEO_SUPPORT_INDEXNOW_ENDPOINTS_OPT);
    $defaults = seo_support_indexnow_default_endpoints();
    if (!is_array($saved)) { $saved = []; }
    foreach ($defaults as $k => &$meta) {
        $meta['enabled'] = isset($saved[$k]['enabled']) ? (bool)$saved[$k]['enabled'] : $meta['enabled'];
        if (!empty($saved[$k]['endpoint'])) {
            $meta['endpoint'] = esc_url_raw($saved[$k]['endpoint']);
        }
    }
    return $defaults;
}

function seo_support_indexnow_get_allowed_post_types() {
    $allowed = get_option(SEO_SUPPORT_INDEXNOW_POSTTYPES_OPT);
    if (!is_array($allowed) || empty($allowed)) {
        $public_types = get_post_types(['public' => true], 'names');
        return array_values($public_types);
    }
    return array_values(array_map('sanitize_key', $allowed));
}

function seo_support_indexnow_event_enabled($event) {
    $events = get_option(SEO_SUPPORT_INDEXNOW_EVENTS_OPT);
    if (!is_array($events) || empty($events)) {
        return in_array($event, ['publish_update', 'delete'], true);
    }
    return !empty($events[$event]);
}

function seo_support_indexnow_log($entry) {
    $log = get_option(SEO_SUPPORT_INDEXNOW_LOG_OPT, []);
    if (!is_array($log)) { $log = []; }
    $entry['time'] = current_time('mysql');
    array_unshift($log, $entry);
    $log = array_slice($log, 0, 50);
    update_option(SEO_SUPPORT_INDEXNOW_LOG_OPT, $log, false);
}

function seo_support_indexnow_send_ping($urls) {
    $enabled = (bool) get_option(SEO_SUPPORT_INDEXNOW_ENABLED_OPT);
    if (!$enabled) return;

    $key = seo_support_indexnow_sanitize_key(get_option(SEO_SUPPORT_INDEXNOW_KEY_OPT));
    if (!$key) return;

    $key_file_ok = seo_support_indexnow_write_key_file($key);

    $host = parse_url(home_url('/'), PHP_URL_HOST);
    $payload = [
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => seo_support_indexnow_get_key_location_url($key),
        'urlList'     => array_values(array_unique(array_filter(array_map('esc_url_raw', (array)$urls))))
    ];

    if (empty($payload['urlList'])) return;

    $endpoints = seo_support_indexnow_get_enabled_endpoints();
    foreach ($endpoints as $slug => $meta) {
        if (empty($meta['enabled'])) continue;
        $endpoint = esc_url_raw($meta['endpoint']);
        $resp = wp_remote_post($endpoint, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode($payload),
            'timeout' => 10,
        ]);
        $code = is_wp_error($resp) ? 0 : wp_remote_retrieve_response_code($resp);
        $msg  = is_wp_error($resp) ? $resp->get_error_message() : wp_remote_retrieve_body($resp);
        seo_support_indexnow_log([
            'endpoint'   => $endpoint,
            'code'       => $code,
            'ok'         => ($code >= 200 && $code < 300),
            'urls'       => $payload['urlList'],
            'keyfile_ok' => $key_file_ok,
            'message'    => is_string($msg) ? wp_trim_words($msg, 30, '…') : '',
        ]);
    }
}

function seo_support_indexnow_debounce($url, $seconds = 60) {
    $key = 'seo_support_indexnow_recent_' . md5($url);
    if (get_transient($key)) return true;
    set_transient($key, 1, $seconds);
    return false;
}

// ---- Hooks -------------------------------------------------------------------
function seo_support_indexnow_on_transition($new_status, $old_status, $post) {
    if (!is_a($post, 'WP_Post')) return;
    if (!seo_support_indexnow_event_enabled('publish_update')) return;
    $allowed_types = seo_support_indexnow_get_allowed_post_types();
    if (!in_array($post->post_type, $allowed_types, true)) return;
    if ($new_status === 'publish') {
        $url = get_permalink($post);
        if (!$url) return;
        if (seo_support_indexnow_debounce($url)) return;
        seo_support_indexnow_send_ping([$url]);
    }
}
add_action('transition_post_status', 'seo_support_indexnow_on_transition', 10, 3);

function seo_support_indexnow_on_delete($post_id) {
    if (!seo_support_indexnow_event_enabled('delete')) return;
    $post = get_post($post_id);
    if (!$post) return;
    $allowed_types = seo_support_indexnow_get_allowed_post_types();
    if (!in_array($post->post_type, $allowed_types, true)) return;
    $url = get_permalink($post);
    if (!$url) return;
    if (seo_support_indexnow_debounce($url)) return;
    seo_support_indexnow_send_ping([$url]);
}
add_action('before_delete_post', 'seo_support_indexnow_on_delete', 10, 1);
add_action('trashed_post',        'seo_support_indexnow_on_delete', 10, 1);

// ---- Settings Section (render as tab in seosupport) --------------------------
function seo_support_indexnow_handle_post_on_seosupport_page() {
    if (!current_user_can('manage_options')) return;
    if (empty($_POST['seo_support_indexnow_submit'])) return;
    if (!isset($_POST['seo_support_indexnow_nonce']) || !wp_verify_nonce($_POST['seo_support_indexnow_nonce'], 'seo_support_indexnow_save')) return;

    $enabled    = isset($_POST['enabled']) ? 1 : 0;
    $key        = isset($_POST['key']) ? seo_support_indexnow_sanitize_key($_POST['key']) : '';
    $post_types = isset($_POST['post_types']) ? array_map('sanitize_key', (array)$_POST['post_types']) : [];

    $events = [
        'publish_update' => !empty($_POST['event_publish_update']) ? 1 : 0,
        'delete'         => !empty($_POST['event_delete']) ? 1 : 0,
    ];

    $endpoints = seo_support_indexnow_default_endpoints();
    foreach ($endpoints as $slug => &$meta) {
        $meta['enabled']  = !empty($_POST['endpoint_' . $slug]);
        if (!empty($_POST['endpoint_url_' . $slug])) {
            $meta['endpoint'] = esc_url_raw($_POST['endpoint_url_' . $slug]);
        }
    }

    if (isset($_POST['generate_key']) || ($enabled && !$key)) {
        $key = seo_support_indexnow_generate_key(32);
    }

    update_option(SEO_SUPPORT_INDEXNOW_ENABLED_OPT,   $enabled ? 1 : 0, false);
    update_option(SEO_SUPPORT_INDEXNOW_KEY_OPT,        $key, false);
    update_option(SEO_SUPPORT_INDEXNOW_POSTTYPES_OPT,  $post_types, false);
    update_option(SEO_SUPPORT_INDEXNOW_EVENTS_OPT,     $events, false);
    update_option(SEO_SUPPORT_INDEXNOW_ENDPOINTS_OPT,  $endpoints, false);

    if ($key) {
        $ok = seo_support_indexnow_write_key_file($key);
        if (!$ok) {
            add_settings_error('seo_support_indexnow', 'keyfile', __('Could not write key file to site root. Ensure filesystem is writable or create it manually.', 'seo-support'), 'error');
        }
    }

    if (!empty($_POST['manual_url'])) {
        $manual_url = esc_url_raw(trim($_POST['manual_url']));
        if ($manual_url) {
            seo_support_indexnow_send_ping([$manual_url]);
            add_settings_error('seo_support_indexnow', 'manual', sprintf(__('Manual ping sent for %s', 'seo-support'), esc_html($manual_url)), 'updated');
        }
    }

    add_settings_error('seo_support_indexnow', 'saved', __('Settings saved.', 'seo-support'), 'updated');
}

function seo_support_indexnow_render_tab_content() {
    if (!current_user_can('manage_options')) return;

    $enabled    = (bool) get_option(SEO_SUPPORT_INDEXNOW_ENABLED_OPT);
    $key        = (string) get_option(SEO_SUPPORT_INDEXNOW_KEY_OPT, '');
    $post_types = seo_support_indexnow_get_allowed_post_types();
    $all_types  = get_post_types(['public' => true], 'objects');
    $events     = get_option(SEO_SUPPORT_INDEXNOW_EVENTS_OPT, ['publish_update' => 1, 'delete' => 1]);
    $endpoints  = seo_support_indexnow_get_enabled_endpoints();
    $key_url    = $key ? seo_support_indexnow_get_key_location_url($key) : '';
    $log        = get_option(SEO_SUPPORT_INDEXNOW_LOG_OPT, []);

    settings_errors('seo_support_indexnow');
    ?>
    <div class="tab-content" id="seo-support-tab-indexnow">
        <h2><?php esc_html_e('IndexNow', 'seo-support'); ?></h2>
        <p class="description"><?php esc_html_e('Notify supported search engines about content changes instantly using IndexNow.', 'seo-support'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=seosupport&tab=indexnow')); ?>">
            <?php wp_nonce_field('seo_support_indexnow_save', 'seo_support_indexnow_nonce'); ?>
            <input type="hidden" name="seo_support_indexnow_submit" value="1" />

            <h3 class="title"><?php esc_html_e('General', 'seo-support'); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable IndexNow', 'seo-support'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?> />
                            <?php esc_html_e('Turn on pings for publish/update/delete', 'seo-support'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('API Key', 'seo-support'); ?></th>
                    <td>
                        <input type="text" name="key" value="<?php echo esc_attr($key); ?>" class="regular-text" placeholder="<?php esc_attr_e('32–64 chars (a–z, 0–9, - , _)', 'seo-support'); ?>" />
                        <button class="button" name="generate_key" value="1"><?php esc_html_e('Generate', 'seo-support'); ?></button>
                        <?php if ($key_url): ?>
                            <p class="description"><?php esc_html_e('Key file URL:', 'seo-support'); ?> <a href="<?php echo esc_url($key_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($key_url); ?></a></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3 class="title"><?php esc_html_e('Endpoints', 'seo-support'); ?></h3>
            <table class="form-table" role="presentation">
                <?php foreach ($endpoints as $slug => $meta): ?>
                <tr>
                    <th scope="row"><?php echo esc_html($meta['label']); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="endpoint_<?php echo esc_attr($slug); ?>" value="1" <?php checked(!empty($meta['enabled'])); ?> />
                            <?php esc_html_e('Enable', 'seo-support'); ?>
                        </label>
                        <br/>
                        <input type="url" name="endpoint_url_<?php echo esc_attr($slug); ?>" value="<?php echo esc_attr($meta['endpoint']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('You can customize the endpoint URL if needed.', 'seo-support'); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3 class="title"><?php esc_html_e('Content Types & Events', 'seo-support'); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Post Types', 'seo-support'); ?></th>
                    <td>
                        <?php foreach ($all_types as $type => $obj): ?>
                            <label style="display:inline-block;margin-right:12px;">
                                <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($type); ?>" <?php checked(in_array($type, $post_types, true)); ?> />
                                <?php echo esc_html($obj->labels->name . ' (' . $type . ')'); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php esc_html_e('Choose which public post types will trigger pings.', 'seo-support'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Events', 'seo-support'); ?></th>
                    <td>
                        <label style="display:block;">
                            <input type="checkbox" name="event_publish_update" value="1" <?php checked(!empty($events['publish_update'])); ?> />
                            <?php esc_html_e('On publish & update', 'seo-support'); ?>
                        </label>
                        <label style="display:block;">
                            <input type="checkbox" name="event_delete" value="1" <?php checked(!empty($events['delete'])); ?> />
                            <?php esc_html_e('On trash & delete', 'seo-support'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3 class="title"><?php esc_html_e('Manual Ping', 'seo-support'); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('URL to ping', 'seo-support'); ?></th>
                    <td>
                        <input type="url" name="manual_url" value="" class="regular-text" placeholder="https://example.com/page/" />
                        <p class="description"><?php esc_html_e('Enter a full URL to notify endpoints immediately.', 'seo-support'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Changes', 'seo-support')); ?>
        </form>

        <h3 class="title" style="margin-top:2em;"><?php esc_html_e('Recent Pings (last 50)', 'seo-support'); ?></h3>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'seo-support'); ?></th>
                    <th><?php esc_html_e('Endpoint', 'seo-support'); ?></th>
                    <th><?php esc_html_e('Status', 'seo-support'); ?></th>
                    <th><?php esc_html_e('Key file', 'seo-support'); ?></th>
                    <th><?php esc_html_e('URLs', 'seo-support'); ?></th>
                    <th><?php esc_html_e('Message', 'seo-support'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log)): ?>
                    <tr><td colspan="6"><?php esc_html_e('No pings yet.', 'seo-support'); ?></td></tr>
                <?php else: foreach ($log as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['time'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['endpoint'] ?? ''); ?></td>
                        <td><?php echo isset($row['code']) ? esc_html((string)$row['code']) : ''; ?><?php echo !empty($row['ok']) ? ' ✓' : ''; ?></td>
                        <td><?php echo !empty($row['keyfile_ok']) ? '✓' : '—'; ?></td>
                        <td style="word-break:break-all;">
                            <?php if (!empty($row['urls']) && is_array($row['urls'])): ?>
                                <?php foreach ($row['urls'] as $u): ?>
                                    <div><a href="<?php echo esc_url($u); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($u); ?></a></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($row['message'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ---- WP-CLI helper ----------------------------------------------------------
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('seo-support indexnow', function($args, $assoc_args) {
        $url = isset($assoc_args['url']) ? esc_url_raw($assoc_args['url']) : '';
        if (!$url) {
            WP_CLI::error('Provide --url=<https://example.com/page/>');
        }
        seo_support_indexnow_send_ping([$url]);
        WP_CLI::success('Ping sent.');
    });
}

?>
