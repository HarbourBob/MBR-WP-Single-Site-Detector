# 🔍 MBR WP Site Detector

**A free WordPress plugin that lets anyone check whether a website is built with WordPress — and uncover the theme, plugins, and version numbers running under the hood.**

Built and maintained by [Robert Palmer](https://littlewebshack.com/) · [Little Web Shack](https://littlewebshack.com/) · [☕ Buy me a coffee](https://buymeacoffee.com/robertpalmer)

---

## Why does this exist?

Whether you're a freelancer scoping a new client, an agency auditing a competitor, or a developer helping someone migrate their site — knowing what's powering a website is genuinely useful information. This plugin puts that capability directly on your own WordPress site, in a clean, branded widget your visitors can use too.

---

## Real-world uses

### 🤝 Scoping a prospective client's website
Before your first call with a potential client, run their site through the detector. You'll instantly see what theme they're using, which plugins are active, and what version of WordPress they're running. Walking into a discovery call already knowing their stack makes you look sharp — and means you can flag problems before they've even hired you.

> *"Their site is running WordPress 5.8 with a theme that hasn't been updated in three years and an abandoned e-commerce plugin. That's a conversation starter."*

### 🚨 Spotting outdated installs
An old WordPress version or a plugin that's been unmaintained for years is a red flag. The version numbers surfaced by the detector let you quickly assess whether a site is a security risk waiting to happen — useful when taking over a client's project, or auditing a site before a rebuild quote.

### 🕵️ Competitor research
Want to know what stack a competitor or industry peer is running? The detector gives you a transparent look at the tools they've chosen — page builders, SEO plugins, performance tools, membership systems. It won't tell you everything, but it tells you more than most people realise is publicly visible.

### 🏗️ Pre-migration assessment
Taking on a migration project? Detect the source site first to understand what you're dealing with before you commit to a price. Knowing the theme and active plugins upfront means no nasty surprises halfway through the job.

### 📊 Adding value to your own site
Drop the `[wp_detector]` shortcode on a page of your agency or freelancer site. It's a practical, interactive tool that demonstrates your knowledge of the WordPress ecosystem and gives visitors a reason to stay — and a reason to come back.

### 🧑‍💻 Developer due diligence
When a client sends you a "can you just fix this site" request, a quick scan tells you whether you're about to wade into a outdated WooCommerce install running three conflicting page builders. Forewarned is forearmed.

---

## Features

- ✅ Detects whether a site is built with WordPress
- 🎨 Identifies the active theme (including theme name from `style.css`)
- 🔌 Lists detected plugins with version numbers where available
- 🏷️ Detects the WordPress version in use
- 🔗 Links directly to each theme and plugin on WordPress.org
- 🎛️ Fully customisable UI via **Settings > Site Detector**
  - Accent colour picker
  - Dark mode (Catppuccin Mocha palette)
  - Glassmorphism frosted-glass effect
- ⚡ AJAX-powered — no page reload required
- 📱 Fully responsive
- 🔒 Secure — nonce verification, URL sanitisation, XSS protection

---

## Installation

1. Download the latest release zip from [littlewebshack.com](https://littlewebshack.com/) or this repository
2. In your WordPress admin go to **Plugins > Add New > Upload Plugin**
3. Upload the zip and click **Install Now**, then **Activate**
4. Add `[wp_detector]` to any page or post
5. Optionally customise the appearance at **Settings > Site Detector**

---

## Shortcode

```
[wp_detector]
```

Place this on any page, post, or widget area. That's all you need.

---

## UI Customisation

Head to **Settings > Site Detector** in your WordPress admin. Changes are previewed live before you save.

| Setting | Description |
|---|---|
| Accent Colour | Colours buttons, badges, borders and headings. Any hex colour. |
| Dark Mode | Switches to a dark Catppuccin Mocha palette. |
| Glassmorphism | Applies a frosted-glass backdrop-blur effect to the widget panel. |

> ⚠️ **Elementor users:** Dark mode and glassmorphism styles are applied on the **front end only**. They will not appear inside the Elementor editor or preview panel. View the published page in a browser tab to see the final result.

---

## How detection works

The plugin makes a server-side request to the target URL (avoiding browser CORS restrictions) and analyses the HTML response for WordPress fingerprints:

- Presence of `/wp-content/` or `/wp-includes/` paths
- WordPress `<meta name="generator">` tag
- `wp-json` API link header

Theme slugs are extracted from stylesheet paths, with a secondary request to `style.css` to retrieve the human-readable theme name. Plugin slugs are matched from `/wp-content/plugins/` references, with version numbers captured from `?ver=` query parameters on enqueued assets.

---

## Limitations

- Only detects plugins that load **frontend assets** — server-side-only plugins won't appear
- Plugin versions depend on the site using `?ver=` parameters on enqueued files (most do, some strip them for performance)
- Sites that obscure WordPress fingerprints (via security plugins or server config) may not be detected
- CORS is not an issue (detection runs server-side), but firewalls or IP blocks on the target server may prevent fetching

---

## Changelog

### v1.6.1
- Fixed plugin version header mismatch
- Fixed regex bug preventing `?ver=` version numbers from being captured

### v1.6.0
- New Settings page (Settings > Site Detector) with live preview
- Accent colour picker using native WordPress colour picker
- Dark mode toggle (Catppuccin Mocha palette)
- Glassmorphism toggle (backdrop-filter blur)
- Plugin detection now surfaces version numbers

### v1.5.8
- Minor bug fixes

### v1.5.7
- Initial release

---

## Requirements

- WordPress 5.0+
- PHP 7.0+

---

## Author

**Robert Palmer** — Freelance WordPress developer based in Cleethorpes, England.

Building free, no-upsell plugins for the WordPress community under the [Little Web Shack](https://littlewebshack.com/) and [Made by Robert](https://madebyrobert.co.uk/) brands.

- 🌐 [littlewebshack.com](https://littlewebshack.com/)
- 🌐 [madebyrobert.co.uk](https://madebyrobert.co.uk/)
- 🐙 [github.com/harbourbob](https://github.com/harbourbob)
- ☕ [buymeacoffee.com/robertpalmer](https://buymeacoffee.com/robertpalmer)

---

## License

GPL v2 or later — see [LICENSE](license.txt)
