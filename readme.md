=== SEO Support ===
Contributors: Damian Kuzmicki
Tags: seo, meta tags, sitemap, robots.txt, title tag, meta description, slug editor, indexnow, schema, woocommerce
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin to manage essential SEO elements: meta titles, descriptions, slugs, robots.txt, XML sitemap, IndexNow, and WooCommerce schema enhancements.

== Description ==

**SEO Support** is a lightweight, modular SEO plugin focused on essentials. No ads, no tracking, no bloat.

Core features:

- Add custom **SEO Title** and **Meta Description** to posts, pages, and public custom post types.
- Edit the **slug** directly in the editor (with permalink preview).
- Live **character counters** with warnings for long titles/descriptions.
- Generate a valid **sitemap index** (`/sitemap.xml`) with automatic sitemaps for post types and taxonomies.
- Edit **robots.txt** from the admin panel.
- **IndexNow** integration (submit URL updates to supported search engines).
- WooCommerce enhancements:
  - Sitemap support for `product` and `product_cat`
  - Optional schema improvements for Google Merchant / Product rich results

== Features ==

- ✅ SEO title + meta description fields (per post)
- ✅ Slug editor + permalink preview
- ✅ Live character counters and length warnings
- ✅ Sitemap index and dynamic sitemaps for post types and taxonomies
- ✅ Robots.txt editor
- ✅ IndexNow support
- ✅ WooCommerce-compatible sitemaps
- ✅ Optional WooCommerce schema enhancement (Merchant Return Policy)
- ✅ Clean code, no tracking, no ads

== WooCommerce Schema (Merchant Return Policy) ==

Google may show Search Console warnings for Product schema such as missing:
- `hasMerchantReturnPolicy` (inside `offers`)

SEO Support can attach a **Merchant Return Policy** object to WooCommerce Product schema offers.

Important:
- The fields for *category / method / fees* use **Schema.org URLs** (enumerated values).
- They are not links to your store policy page.

Typical values:
- Return policy category:
  - https://schema.org/MerchantReturnFiniteReturnWindow
  - https://schema.org/MerchantReturnNotPermitted
- Return method:
  - https://schema.org/ReturnByMail
  - https://schema.org/ReturnInStore
- Return fees:
  - https://schema.org/FreeReturn
  - https://schema.org/ReturnFeesCustomerResponsibility


== WooCommerce Shipping Details (Schema) ==

SEO Support can optionally add `shippingDetails` to WooCommerce Product offers to improve Google Merchant listings.

Shipping Details describe delivery cost and time for structured data only.
They do not affect WooCommerce checkout or shipping calculations.

Fields explanation:

- Destination country: 2-letter country code (example: SE, DE)
- Currency: ISO currency code (example: SEK, EUR)
- Shipping rate: fixed shipping cost (use 0 for free shipping)
- Handling time: order processing time before dispatch (days)
- Transit time: delivery time after dispatch (days)

These values are applied globally to all WooCommerce products.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/seo-support`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Edit any post/page to find the **SEO Settings** meta box
4. Go to **SeoSupport** in the WordPress admin menu for:
   - Sitemap + robots.txt settings
   - IndexNow settings
   - Schema settings (WooCommerce enhancements)

== Frequently Asked Questions ==

= Will this plugin work with WooCommerce? =
Yes. It supports WooCommerce products and product categories in the sitemap. It also includes optional schema enhancements for Product offers.

= Does this plugin support Open Graph tags? =
Not yet.

= Can I submit the sitemap to Google Search Console? =
Yes. Submit `/sitemap.xml` (it is a sitemap index linking to all generated sitemaps).

= Does it support multilingual plugins like WPML or Polylang? =
Not officially, but the SEO fields are stored per post, so they typically work on translated content as well.

= Does SEO Support replace Yoast or Rank Math? =
It is designed as a lightweight alternative focused on essential SEO features. If you need advanced features (content analysis, extensive schema graphs, etc.), you may still prefer larger SEO suites.

== Screenshots ==

1. SEO title, description, and slug editor with live counters
2. Sitemap index with links to post, page, product sitemaps
3. Robots.txt editor in the admin
4. Schema settings (WooCommerce Merchant Return Policy)

== Changelog ==

= 1.1 =
* Added Schema tab (WooCommerce Merchant Return Policy for Product offers)
* Added IndexNow tab

= 1.0 =
* Initial release
* SEO meta box with live counters
* Custom slug support
* Sitemap index and dynamic sitemaps
* Robots.txt editor

== Upgrade Notice ==

= 1.1 =
Adds IndexNow and optional WooCommerce schema enhancement for Merchant Return Policy.

== License ==

This plugin is licensed under the GPLv2 or later.
