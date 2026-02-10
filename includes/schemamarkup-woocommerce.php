<?php
defined('ABSPATH') || exit;

add_filter('woocommerce_structured_data_product', function ($markup, $product) {
    if (!is_array($markup) || empty($markup['offers'])) {
        return $markup;
    }

    $policy = seosupport_schema_get_return_policy_node();
    $shipping = seosupport_schema_get_shipping_details_node();

    if (!$policy && !$shipping) {
        return $markup;
    }

    if (is_array($markup['offers'])) {
        if (isset($markup['offers']['@type'])) {
            $offer = $markup['offers'];

            if ($policy) {
                $offer = seosupport_schema_attach_policy_to_offer($offer, $policy);
            }
            if ($shipping) {
                $offer = seosupport_schema_attach_shipping_to_offer($offer, $shipping);
            }

            $markup['offers'] = $offer;
            return $markup;
        }

        foreach ($markup['offers'] as $i => $offer) {
            if (!is_array($offer) || empty($offer['@type']) || $offer['@type'] !== 'Offer') {
                continue;
            }

            if ($policy) {
                $offer = seosupport_schema_attach_policy_to_offer($offer, $policy);
            }
            if ($shipping) {
                $offer = seosupport_schema_attach_shipping_to_offer($offer, $shipping);
            }

            $markup['offers'][$i] = $offer;
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

function seosupport_schema_attach_shipping_to_offer(array $offer, array $shipping_details): array {
    if (!empty($offer['shippingDetails'])) {
        return $offer;
    }
    $offer['shippingDetails'] = $shipping_details;
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

function seosupport_schema_get_shipping_details_node(): ?array {
    $opt = get_option('seosupport_schema_settings', []);
    if (!is_array($opt)) {
        $opt = [];
    }

    if (empty($opt['shipping_enabled'])) {
        return null;
    }

    $country = !empty($opt['shipping_country']) ? strtoupper(trim((string) $opt['shipping_country'])) : '';
    $currency = !empty($opt['shipping_currency']) ? strtoupper(trim((string) $opt['shipping_currency'])) : '';
    $rate_value = isset($opt['shipping_rate_value']) ? (float) $opt['shipping_rate_value'] : -1;

    $handling_min = isset($opt['shipping_handling_min']) ? (int) $opt['shipping_handling_min'] : -1;
    $handling_max = isset($opt['shipping_handling_max']) ? (int) $opt['shipping_handling_max'] : -1;
    $transit_min  = isset($opt['shipping_transit_min']) ? (int) $opt['shipping_transit_min'] : -1;
    $transit_max  = isset($opt['shipping_transit_max']) ? (int) $opt['shipping_transit_max'] : -1;

    if ($country === '' || $currency === '' || $rate_value < 0) {
        return null;
    }

    if ($handling_min < 0 || $handling_max < 0 || $transit_min < 0 || $transit_max < 0) {
        return null;
    }

    if ($handling_min > $handling_max || $transit_min > $transit_max) {
        return null;
    }

    return [
        [
            '@type' => 'OfferShippingDetails',
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => $country,
            ],
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'value' => (string) $rate_value,
                'currency' => $currency,
            ],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => $handling_min,
                    'maxValue' => $handling_max,
                    'unitCode' => 'd',
                ],
                'transitTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => $transit_min,
                    'maxValue' => $transit_max,
                    'unitCode' => 'd',
                ],
            ],
        ]
    ];
}
