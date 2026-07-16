=== Trackly ===
Contributors: trackly
Tags: analytics, google analytics, ga4, heatmaps, visitor tracking, stats, dashboard, telemetry
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modern Google Analytics 4 dashboard and click heatmap tracker client for WordPress with AI insights.

== Description ==

Trackly is a GDPR-compliant, high-performance plugin that brings Google Analytics 4 reports and click heatmaps directly to your WordPress website.

Key Features:
* **GDPR Compliance**: Integrates seamlessly with cookie consent plugins like Borlabs, Complianz, CLI, and Google Consent Mode v2.
* **Service Account Integration**: Secure, server-side authentication using Google Cloud service account JSON credentials encrypted via AES-256-GCM.
* **Click Heatmaps**: View coordinates of user clicks on any page using a native DocumentFragment canvas.
* **Custom GA4 Event Builder**: Visually map and capture custom events on buttons or links on any webpage.
* **Dynamic Proxy Whitelisting**: Automated weekly Cloudflare and reverse proxy range sync to avoid click log client IP blocking.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/trackly` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your GA4 Property ID and Service Account JSON credentials under the Settings menu.

== Frequently Asked Questions ==

= Does this plugin support IPv6? =
Yes! Cloudflare and reverse proxy whitelists support both IPv4 and IPv6 subnet ranges natively.

== Screenshots ==

1. The main administrative analytics dashboard.
2. Glassmorphic front-end client overlay displaying page statistics.

== Changelog ==

= 1.0.0 =
* Initial Release.
