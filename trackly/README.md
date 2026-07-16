# Trackly

[Türkçe Versiyonu](README.tr.md)

Trackly is a modern, stunning, and lightweight Google Analytics 4 (GA4) dashboard, page-level statistics client, local click heatmap tracker, and custom event builder for WordPress. Designed for site owners, developers, and marketers who want direct analytics insights without leaving their WordPress dashboard.

---

## Key Features

- **Stunning GA4 Dashboard:** View pageviews, unique visitors, bounce rates, and average session duration with beautiful charts.
- **Traffic & Device Analysis:** Identify where your visitors come from (referrers, direct, organic search) and what devices they use (desktop, mobile, tablet).
- **Page-Level Statistics:** A glassmorphism overlay bar appears directly in the frontend for administrators to check stats for the active page.
- **Local Click Heatmaps:** Capture and visualize where visitors are clicking on your pages without third-party heatmap scripts.
- **GA4 Custom Event Builder:** Create custom Google Analytics tracking events interactively by selecting elements (buttons, links) directly from the page layout.
- **AI-Powered Insights:** Get automatic suggestions on how to improve content engagement based on traffic trend characteristics.
- **GDPR & Consent Compliant:** Features session-based sampling rate settings and respects popular cookie consent plugins (Borlabs, Complianz, CLI, and Google Consent Mode v2).

---

## Installation

1. Upload the `trackly` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to the **Trackly** menu in your WordPress admin sidebar to configure settings.

---

## Configuration

To connect Trackly to your Google Analytics 4 property:

1. **GA4 Property ID:** Enter your numeric GA4 Property ID (can be found in GA4 > Admin > Property Settings).
2. **Service Account JSON Key:**
   - Go to the [Google Cloud Console](https://console.cloud.google.com/).
   - Create a project (or select an existing one) and enable the **Google Analytics Data API**.
   - Create a **Service Account** and generate a **JSON key**.
   - Copy the generated email address of the Service Account and add it as a **Viewer** under your Google Analytics 4 Property access management.
   - Paste the contents of the downloaded JSON key file into the textarea in **Trackly > Settings**.
3. **Save Settings:** Click "Save Settings" to complete the integration.
4. **Demo Mode:** If you do not have credentials yet, keep the **Demo Mode** setting checked to test the dashboard and frontend overlays with realistic mock data.

---

## Internationalization & Translation

Trackly is fully translation-ready and prepared for localizations. All user-facing strings are wrapped in WordPress standard translation helper functions (`__()`, `_e()`, `esc_html__()`, etc.) using the `trackly` text domain.

### Translating to Your Language

You can easily translate Trackly to your language of choice using standard translation software:

1. **Loco Translate Plugin (Recommended):**
   - Install and activate the **Loco Translate** plugin on your WordPress site.
   - Go to **Loco Translate > Plugins** and select **Trackly**.
   - Click **New Language**, select your language, and start translating the strings directly in your browser.
2. **Poedit:**
   - Generate a `.pot` file from the plugin codebase using tools like WP-CLI or Poedit.
   - Create a new translation file (`trackly-[locale].po` and `trackly-[locale].mo`), translate the strings, and place them in the `languages` folder inside the plugin directory.

---

## License

GPLv2 or later. See license details in individual file headers.
