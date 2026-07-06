=== Ledger Links ===
Contributors: ledgerlinks
Tags: affiliate links, link cloaking, broken link checker, click tracking, csv import
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Affiliate link cloaking with a broken-link checker, honest click analytics, and CSV import/export — free, no upsell nags.

== Description ==

Ledger Links cloaks and tracks your affiliate links without gating the features every other link-cloaking plugin charges for.

**Free, forever:**

* Unlimited cloaked links with 301/302/307 redirect types
* Automatic broken-link checker, twice a day, with a manual "check now" button
* Click analytics with bot-filtering and self-click exclusion switched on by default — no manual setup to get an honest count
* Device targeting: send mobile visitors to a different URL than desktop
* CSV import and export, no row limits
* No in-admin upsell popups, ever

**Pro (optional):** geo-redirects, an autolinker for keyword-to-link automation, advanced analytics (referrer cohorts, conversion tracking), and multi-site licensing for agencies.

= Why free tier this generous? =

Every competitor we looked at gates broken-link checking and CSV import/export behind a paid tier, and every one of them has real user complaints about inflated click counts from bots and self-clicks. We built the fix for both into the free tier instead of the paid one.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ledger-links`, or install through the WordPress plugins screen directly.
2. Activate the plugin.
3. Go to Ledger Links in your admin menu and create your first link.

== Frequently Asked Questions ==

= Will the free tier ever get locked down later? =

No. The features listed as free above stay free. Pro only adds new capability on top.

= How does the broken-link checker work? =

A scheduled task checks every link's target URL twice a day and flags anything returning an error or an unreachable response. You can also trigger a check manually at any time.

= How is click tracking different from other plugins? =

Bot filtering and self-click exclusion are on by default, not an option you have to find and enable. The dashboard shows the clean count first; the raw count is shown alongside it for comparison.

== Changelog ==

= 1.0.0 =
* Initial release: link cloaking, broken-link checker, honest analytics, CSV import/export, device targeting.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
