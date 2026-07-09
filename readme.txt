=== Trellink ===
Contributors: ledgerlinks
Tags: affiliate links, link cloaking, broken link checker, click tracking, csv import
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Affiliate link cloaking with a broken-link checker, click analytics, and CSV import/export.

== Description ==

Trellink cloaks and tracks affiliate links, with built-in link-health monitoring and click analytics.

**Free features:**

* Unlimited cloaked links with 301/302/307 redirect types
* Automatic broken-link checker, twice a day, with a manual "check now" button
* Click analytics with configurable bot-filtering and self-click exclusion
* Device targeting: send mobile visitors to a different URL than desktop
* CSV import and export, no row limits

**Pro (optional):** geo-redirects, an autolinker for keyword-to-link automation, advanced analytics (referrer cohorts, conversion tracking), and multi-site licensing for agencies.

== External Services ==

This plugin connects to the Lemon Squeezy API to activate and validate Pro license keys. This is only used if you enter a Pro license key in Settings — the free tier never contacts this service.

* **What the service is and what it's used for:** Lemon Squeezy is a payment and licensing provider. When you enter a Pro license key, the plugin sends that key to Lemon Squeezy to confirm it is valid and activate Pro features on your site. The plugin also re-checks the key's validity automatically once a week, so that a cancelled subscription loses Pro access without you needing to do anything manually.
* **What data is sent and when:** the license key you entered, and your site's home URL (`home_url()`), sent when you click "Activate" and again during the weekly automatic re-check.
* **Service links:** [Terms of Service](https://www.lemonsqueezy.com/terms), [Privacy Policy](https://www.lemonsqueezy.com/privacy)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/trellink`, or install through the WordPress plugins screen directly.
2. Activate the plugin.
3. Go to Trellink in your admin menu and create your first link.

== Frequently Asked Questions ==

= How does the broken-link checker work? =

A scheduled task checks every link's target URL twice a day and flags anything returning an error or an unreachable response. You can also trigger a check manually at any time.

= How does click tracking handle bots and my own clicks? =

Bot filtering and self-click exclusion can be toggled in Settings. When enabled, the dashboard shows a filtered "clean" count alongside the raw count.

= Does this plugin track visitors? =

The plugin records a click (timestamp, device type, browser, referrer, and a one-way hash of the visitor's IP address) each time someone follows one of your cloaked links, so you can see basic usage stats for your own links. This data stays in your own WordPress database — it is not sent to any external service. IP addresses are hashed with a secret generated and stored by the plugin itself (not reused from WordPress core) before being stored; the raw IP address is never saved.

== Changelog ==

= 1.0.0 =
* Initial release: link cloaking, broken-link checker, click analytics, CSV import/export, device targeting.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
