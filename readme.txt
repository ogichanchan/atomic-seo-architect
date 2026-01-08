=== Atomic Seo Architect ===
Contributors: ogichanchan
Tags: wordpress, plugin, tool, admin, performance, seo, meta, title, description, robots, canonical
Requires at least: 6.2
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Atomic SEO Architect is a lightweight and efficient PHP-only WordPress plugin designed to provide essential SEO functionalities. Acting as an "architect" for your site's search engine optimization, it offers a streamlined approach to managing meta titles, descriptions, and robots settings directly within WordPress.

The plugin introduces a dedicated settings page under 'Settings' in your WordPress admin dashboard, allowing you to configure site-wide default SEO parameters such as a global title prefix and suffix, a default meta description, and options for globally preventing indexing (noindex) or link following (nofollow) for your entire site.

For granular control, Atomic SEO Architect adds a custom meta box to individual post and page edit screens. This meta box enables you to override global settings and define specific SEO titles, meta descriptions, robots directives (noindex, nofollow), and canonical URLs for each piece of content. This "atomic" control ensures that you can precisely tailor your SEO strategy on a page-by-page basis.

By leveraging WordPress hooks, the plugin dynamically filters document titles and outputs essential meta tags (description, robots, canonical) directly into your site's `<head>` section, ensuring proper communication with search engines. Its focus on simplicity means no external libraries or complex frameworks, making it a truly lean and performant SEO utility.

This plugin is open source. Report bugs at: https://github.com/ogichanchan/atomic-seo-architect

== Installation ==
1. Upload to /wp-content/plugins/
2. Activate

== Changelog ==
= 1.0.0 =
* Initial release.