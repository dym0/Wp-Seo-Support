<?php

function seosupport_generate_sitemap() {
    $sitemaps_dir = ABSPATH;
    $sitemap_index_path = $sitemaps_dir . 'sitemap.xml';
    $sitemaps = [];

    // 1. Pobierz wszystkie publiczne typy postów (w tym produkty jeśli WooCommerce jest zainstalowane)
    $post_types = get_post_types(['public' => true], 'names');

    foreach ($post_types as $post_type) {
        $sitemap_filename = "sitemap-{$post_type}.xml";
        $sitemap_url = home_url("/{$sitemap_filename}");
        seosupport_generate_post_type_sitemap($post_type, $sitemaps_dir . $sitemap_filename);
        $sitemaps[] = [
            'loc' => $sitemap_url,
            'lastmod' => current_time('c')
        ];
    }

    // 2. Taksonomie (category, post_tag, product_cat, product_tag)
    $taxonomy_sitemap_filename = 'sitemap-taxonomies.xml';
    $taxonomy_sitemap_url = home_url("/{$taxonomy_sitemap_filename}");
    seosupport_generate_taxonomy_sitemap($sitemaps_dir . $taxonomy_sitemap_filename);
    $sitemaps[] = [
        'loc' => $taxonomy_sitemap_url,
        'lastmod' => current_time('c')
    ];

    // 3. Sitemap index
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $index = $xml->createElement('sitemapindex');
    $index->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    foreach ($sitemaps as $entry) {
        $sitemap = $xml->createElement('sitemap');
        $loc = $xml->createElement('loc', $entry['loc']);
        $lastmod = $xml->createElement('lastmod', $entry['lastmod']);
        $sitemap->appendChild($loc);
        $sitemap->appendChild($lastmod);
        $index->appendChild($sitemap);
    }

    $xml->appendChild($index);
    $xml->save($sitemap_index_path);
	
	
update_option('seosupport_last_generated', current_time('mysql'));
}

function seosupport_generate_post_type_sitemap($post_type, $path) {
    $posts = get_posts([
        'numberposts' => -1,
        'post_type' => $post_type,
        'post_status' => 'publish'
    ]);

    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

    foreach ($posts as $post) {
        $url = $xml->createElement('url');
        $loc = $xml->createElement('loc', get_permalink($post));
        $lastmod = $xml->createElement('lastmod', get_post_modified_time('c', true, $post));
        $url->appendChild($loc);
        $url->appendChild($lastmod);

        // Featured image
        if (has_post_thumbnail($post)) {
            $thumb_id = get_post_thumbnail_id($post);
            $thumb_url = wp_get_attachment_url($thumb_id);
            $thumb_title = get_the_title($thumb_id);

            $image = $xml->createElement('image:image');
            $image->appendChild($xml->createElement('image:loc', esc_url($thumb_url)));
            if ($thumb_title) {
                $image->appendChild($xml->createElement('image:title', htmlspecialchars($thumb_title)));
            }
            $url->appendChild($image);
        }

        // All <img> tags in content
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);
        if (!empty($matches[1])) {
           
			foreach ($matches[1] as $img_url) {
			//pass throu image already added
				if (isset($thumb_url) && $img_url === $thumb_url) {
					continue;
				}

				$image = $xml->createElement('image:image');
				$image->appendChild($xml->createElement('image:loc', esc_url($img_url)));
				$url->appendChild($image);
			}
        }

        $urlset->appendChild($url);
    }

    $xml->appendChild($urlset);
    $xml->save($path);
}


function seosupport_generate_taxonomy_sitemap($path) {
    $taxonomies = ['category', 'post_tag', 'product_cat', 'product_tag'];

    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    foreach ($taxonomies as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) continue;
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);

        foreach ($terms as $term) {
            $url = $xml->createElement('url');
            $loc = $xml->createElement('loc', get_term_link($term));
            $lastmod = $xml->createElement('lastmod', current_time('c'));
            $url->appendChild($loc);
            $url->appendChild($lastmod);
            $urlset->appendChild($url);
        }
    }

    $xml->appendChild($urlset);
    $xml->save($path);
}

