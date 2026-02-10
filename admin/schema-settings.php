<?php
defined('ABSPATH') || exit;

add_action('admin_init', function () {
    register_setting('seosupport_schema', 'seosupport_schema_settings', [
        'type' => 'array',
        'sanitize_callback' => 'seosupport_schema_sanitize_settings',
        'default' => [],
    ]);
});

function seosupport_schema_sanitize_settings($input) {
    $out = [];

    $out['return_policy_enabled'] = !empty($input['return_policy_enabled']) ? 1 : 0;

    $out['return_policy_country'] = isset($input['return_policy_country'])
        ? preg_replace('/[^A-Za-z]/', '', strtoupper(trim((string) $input['return_policy_country'])))
        : '';

    $out['return_policy_days'] = isset($input['return_policy_days'])
        ? max(0, (int) $input['return_policy_days'])
        : 0;

    $out['return_policy_category'] = isset($input['return_policy_category'])
        ? esc_url_raw(trim((string) $input['return_policy_category']))
        : '';

    $out['return_policy_method'] = isset($input['return_policy_method'])
        ? esc_url_raw(trim((string) $input['return_policy_method']))
        : '';

    $out['return_policy_fees'] = isset($input['return_policy_fees'])
        ? esc_url_raw(trim((string) $input['return_policy_fees']))
        : '';


    $out['shipping_enabled'] = !empty($input['shipping_enabled']) ? 1 : 0;

$out['shipping_country'] = isset($input['shipping_country'])
    ? preg_replace('/[^A-Za-z]/', '', strtoupper(trim((string) $input['shipping_country'])))
    : '';

$out['shipping_currency'] = isset($input['shipping_currency'])
    ? preg_replace('/[^A-Za-z]/', '', strtoupper(trim((string) $input['shipping_currency'])))
    : '';

$out['shipping_rate_value'] = isset($input['shipping_rate_value'])
    ? (float) $input['shipping_rate_value']
    : 0;

$out['shipping_handling_min'] = isset($input['shipping_handling_min']) 
    ? max(0, (int) $input['shipping_handling_min']) : 0;

$out['shipping_handling_max'] = isset($input['shipping_handling_max']) 
    ? max(0, (int) $input['shipping_handling_max']) : 0;

$out['shipping_transit_min']  = isset($input['shipping_transit_min'])  
    ? max(0, (int) $input['shipping_transit_min'])  : 0;

$out['shipping_transit_max']  = isset($input['shipping_transit_max'])  
    ? max(0, (int) $input['shipping_transit_max'])  : 0;    

    return $out;
}

function seo_support_schema_render_tab_content() {
    $opt = get_option('seosupport_schema_settings', []);
    if (!is_array($opt)) {
        $opt = [];
    }

    $ship_enabled = !empty($opt['shipping_enabled']);
    $ship_country = $opt['shipping_country'] ?? 'SE';
    $ship_currency = $opt['shipping_currency'] ?? 'SEK';
    $ship_rate = isset($opt['shipping_rate_value']) ? (float) $opt['shipping_rate_value'] : 0;

    $ship_handling_min = isset($opt['shipping_handling_min']) ? (int) $opt['shipping_handling_min'] : 0;
    $ship_handling_max = isset($opt['shipping_handling_max']) ? (int) $opt['shipping_handling_max'] : 1;
    $ship_transit_min  = isset($opt['shipping_transit_min']) ? (int) $opt['shipping_transit_min'] : 1;
    $ship_transit_max  = isset($opt['shipping_transit_max']) ? (int) $opt['shipping_transit_max'] : 3;

    



    $enabled  = !empty($opt['return_policy_enabled']);
    $country  = $opt['return_policy_country'] ?? 'SE';
    $days     = isset($opt['return_policy_days']) ? (int) $opt['return_policy_days'] : 14;
    $category = $opt['return_policy_category'] ?? 'https://schema.org/MerchantReturnFiniteReturnWindow';
    $method   = $opt['return_policy_method'] ?? 'https://schema.org/ReturnByMail';
    $fees     = $opt['return_policy_fees'] ?? 'https://schema.org/FreeReturn';
    ?>
    <div class="wrap">
        <h2>Schema Markup</h2>

        <form method="post" action="options.php">
            <?php settings_fields('seosupport_schema'); ?>

            <h3>Merchant Return Policy</h3>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Enable</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="seosupport_schema_settings[return_policy_enabled]"
                                   value="1" <?php checked($enabled); ?>>
                            Attach return policy to WooCommerce product offers
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Applicable country</th>
                    <td>
                        <input type="text"
                               name="seosupport_schema_settings[return_policy_country]"
                               value="<?php echo esc_attr($country); ?>"
                               maxlength="2"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Return window (days)</th>
                    <td>
                        <input type="number"
                               name="seosupport_schema_settings[return_policy_days]"
                               value="<?php echo esc_attr($days); ?>"
                               min="0">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Return policy category</th>
                    <td>
                        <input type="url"
                               name="seosupport_schema_settings[return_policy_category]"
                               value="<?php echo esc_attr($category); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Return method</th>
                    <td>
                        <input type="url"
                               name="seosupport_schema_settings[return_policy_method]"
                               value="<?php echo esc_attr($method); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Return fees</th>
                    <td>
                        <input type="url"
                               name="seosupport_schema_settings[return_policy_fees]"
                               value="<?php echo esc_attr($fees); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>

        <h3>Shipping Details</h3>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Enable</th>
                <td>
                    <label>
                        <input type="checkbox"
                            name="seosupport_schema_settings[shipping_enabled]"
                            value="1" <?php checked($ship_enabled); ?>>
                        Add shippingDetails to WooCommerce product offers
                    </label>
                    <p class="description">
                        Enables shipping information in structured data for all WooCommerce products.
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Destination country</th>
                <td>
                    <input type="text"
                        name="seosupport_schema_settings[shipping_country]"
                        value="<?php echo esc_attr($ship_country); ?>"
                        maxlength="2"
                        class="regular-text">
                    <p class="description">
                        2-letter country code. Example: <code>SE</code>, <code>DE</code>, <code>PL</code>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Currency</th>
                <td>
                    <input type="text"
                        name="seosupport_schema_settings[shipping_currency]"
                        value="<?php echo esc_attr($ship_currency); ?>"
                        maxlength="3"
                        class="regular-text">
                    <p class="description">
                        ISO currency code. Example: <code>SEK</code>, <code>EUR</code>, <code>USD</code>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Shipping rate</th>
                <td>
                    <input type="number"
                        name="seosupport_schema_settings[shipping_rate_value]"
                        value="<?php echo esc_attr($ship_rate); ?>"
                        min="0"
                        step="0.01">
                    <p class="description">
                        Fixed shipping cost. Use <code>0</code> for free shipping. Example: <code>99</code>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Handling time (days)</th>
                <td>
                    <input type="number"
                        name="seosupport_schema_settings[shipping_handling_min]"
                        value="<?php echo esc_attr($ship_handling_min); ?>"
                        min="0"
                        step="1"
                        style="width:90px;">
                    <span style="margin:0 8px;">to</span>
                    <input type="number"
                        name="seosupport_schema_settings[shipping_handling_max]"
                        value="<?php echo esc_attr($ship_handling_max); ?>"
                        min="0"
                        step="1"
                        style="width:90px;">
                    <p class="description">
                        Order processing time before dispatch. Example: <code>0 – 1</code> days
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Transit time (days)</th>
                <td>
                    <input type="number"
                        name="seosupport_schema_settings[shipping_transit_min]"
                        value="<?php echo esc_attr($ship_transit_min); ?>"
                        min="0"
                        step="1"
                        style="width:90px;">
                    <span style="margin:0 8px;">to</span>
                    <input type="number"
                        name="seosupport_schema_settings[shipping_transit_max]"
                        value="<?php echo esc_attr($ship_transit_max); ?>"
                        min="0"
                        step="1"
                        style="width:90px;">
                    <p class="description">
                        Delivery time after dispatch. Example: <code>1 – 3</code> days
                    </p>
                </td>
            </tr>
        </table>


            <?php submit_button('Save schema settings'); ?>
        </form>

        <div class="notice notice-info" style="padding:12px 14px; margin: 12px 0 16px 0;">
             <p><strong>How these fields work</strong></p>
             <p>This section adds <code>hasMerchantReturnPolicy</code> to WooCommerce Product schema (<code>Offer</code>) to improve Google Merchant listings and reduce Search Console warnings.</p>
            
             <ul style="margin: 8px 0 0 18px; list-style: disc;">
                <li><strong>Return policy category</strong>, <strong>Return method</strong>, and <strong>Return fees</strong> must be Schema.org values (URLs). They are not links to your store policy page.</li>
                <li><strong>Applicable country</strong> is a 2-letter country code (example: <code>SE</code>).</li>
                <li><strong>Return window</strong> is the number of days customers can return products (example: <code>14</code>).</li>
            </ul>

            <p style="margin-top:10px;"><strong>Typical values</strong></p>

            <ul style="margin: 8px 0 0 18px; list-style: disc;">
                <li>Category: <code>https://schema.org/MerchantReturnFiniteReturnWindow</code></li>
                <li>Method: <code>https://schema.org/ReturnByMail</code> or <code>https://schema.org/ReturnInStore</code></li>
                <li>Fees: <code>https://schema.org/FreeReturn</code> or <code>https://schema.org/ReturnFeesCustomerResponsibility</code></li>
            </ul>
        </div>


    </div>




    
    <?php
}
