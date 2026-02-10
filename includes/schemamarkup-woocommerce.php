<?php
defined('ABSPATH') || exit;

add_filter('woocommerce_structured_data_product', function ($markup, $product) {
    if (!is_array($markup) || empty($markup['offers'])) {
        return $markup;
    }

    $policy = seosupport_schema_get_return_policy_node();
    if (!$policy) {
        return $markup;
    }

    if (is_array($markup['offers'])) {
        if (isset($markup['offers']['@type'])) {
            $markup['offers'] = seosupport_schema_attach_policy_to_offer($markup['offers'], $policy);
            return $markup;
        }

        foreach ($markup['offers'] as $i => $offer) {
            if (is_array($offer) && isset($offer['@type']) && $offer['@type'] === 'Offer') {
                $markup['offers'][$i] = seosupport_schema_attach_policy_to_offer($offer, $policy);
            }
        }
    }

    return $markup;
}, 50, 2);

function seosupport_schema_attach_policy_to_offer(array $offer, array $policy): array {
    if (!empty($offer['hasMerchantReturnPolicy'])) {
        return $offer;
    }
    $offer['hasMerchantReturnPolicy'] = $policy;
    return $offer;
}

function seosupport_schema_get_return_policy_node(): ?array {
    $opt = get_option('seosupport_schema_settings', []);
    if (!is_array($opt)) {
        $opt = [];
    }

    if (empty($opt['return_policy_enabled'])) {
        return null;
    }

    $country = !empty($opt['return_policy_country']) ? strtoupper(trim((string) $opt['return_policy_country'])) : '';
    $category = !empty($opt['return_policy_category']) ? trim((string) $opt['return_policy_category']) : '';
    $days = isset($opt['return_policy_days']) ? (int) $opt['return_policy_days'] : 0;
    $method = !empty($opt['return_policy_method']) ? trim((string) $opt['return_policy_method']) : '';
    $fees = !empty($opt['return_policy_fees']) ? trim((string) $opt['return_policy_fees']) : '';

    if ($country === '' || $category === '' || $days <= 0 || $method === '' || $fees === '') {
        return null;
    }

    return [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => $country,
        'returnPolicyCategory' => $category,
        'merchantReturnDays' => $days,
        'returnMethod' => $method,
        'returnFees' => $fees,
    ];
}
