v7.6 - 2026-07-02
====
Feature: VAT invoices are now generated automatically at the moment a sale, service
sale, or subscription sale completes — mandatory and immediate for business buyers
(EU/Belgian VAT rules require an invoice for every B2B sale), and optional for
individual buyers via a new "I want a VAT invoice for this purchase" checkbox at
checkout. Previously an invoice could be created much later, the first time someone
happened to click "Download" on the receipt link, which made its invoice date
unrelated to the actual purchase date.

Feature: individual buyers who did not request an invoice at checkout can still
request one afterwards from `/my-services`, within 6 months of the purchase date
(`BuyCoursesPlugin::INVOICE_REQUEST_WINDOW_MONTHS`). The invoice is generated at the
moment of the request, not backdated.

Feature: every completed purchase (invoiced or not) now also gets a non-fiscal
Receipt document (`receipt.php`), always available regardless of the invoicing
setting, showing the VAT amount charged, the seller's VAT ID, the payment method,
and the payment gateway's own transaction ID when available (captured from Stripe,
PayPal, TPV Redsys, and Culqi; not available for Cecabank or bank transfer).

Feature: services can now declare which AI course-creation features they grant
access to (learning path generator, exercise generator, open-answers grader, tutor
chatbot, task grader, content analyser, image generator, glossary terms generator),
configurable per service and shown to the buyer on the catalog and confirmation
pages.

Feature: services can be duplicated from the admin list ("Copy" action), and the
catalog can filter/group renewable services into monthly/yearly billing-cycle tabs.

Fix: service purchase eligibility checks (`canCurrentUserBuyService()`) now
correctly gate access when a service is restricted to specific users/courses.

Fix: multilingual (multiple-language) HTML service descriptions are now filtered
down to the visitor's language correctly instead of showing every language's block
concatenated together.

Fix: course/session/service checkout now goes through a single unified purchase
page instead of three near-duplicate ones, fixing several navigation
inconsistencies between them.

Fix: an edge case in the course-creation purchase limit check and course/session
purchase flow that could under- or over-count a user's already-purchased items.

Fix: the "Courses/Sessions" tab on the sales report pages (sales_report.php,
service_sales_report.php, subscription_sales_report.php) showed the raw string
"CourseSessionBlock" instead of its translation, because the template called the
core `get_lang` filter instead of `get_plugin_lang('BuyCoursesPlugin')` — this term
was only ever defined in the plugin's own language files.

Fix: the "Search" filter card on the same three sales report pages had its form
elements (radio buttons, the "Order status" select, the Search button) touching the
card's border with no internal padding, breaking the platform's 8-point spacing.
The `formShell` wrapper div relied entirely on nested `[&_.form-group]:p-5`-style
selectors for its padding, but Chamilo's `FormValidator` renders this form's fields
inside `<div class="row mb-3">` wrappers, not `.form-group` — so that padding rule
never matched anything. Added padding directly on the wrapper itself.

Feature: the "Info" button in service_sales_report.php's Actions column now shows
much more: the buyer's name (linked to their admin profile), username, email,
IP address (when recorded), full VAT/tax breakdown, seller VAT number, buyer
VAT number/business name for business buyers, the payment method as text, and the
payment gateway's transaction ID when available — the same information a receipt
shows. The endpoint was rewritten to return structured JSON instead of an HTML
string that the browser scraped back into plain text (a fragile round-trip that
also silently mangled the "Service information"/"Sale information" section
titles, since a `<legend>` closing tag wasn't one of the line-break markers the
scraper looked for).

Fix: the sales report tables showed the payment method as a raw integer for
Stripe, TPV Redsys, and Cecabank sales (only PayPal/bank transfer/Culqi were
mapped by hand in the template) — now uses `BuyCoursesPlugin::getPaymentTypes()`
for full, consistent coverage. Also fixed the price column silently losing its
currency sign: the code read a `sale.iso_code` field that `getServiceSale()` never
actually sets, so every amount fell back to a bare, currency-less number.

Fix: `Template::get_icon_path()`, called as a fallback when a service/product has
no image, does not exist on that class — this method has never existed, so any
service or product sale without a custom image made the request that renders it
throw a fatal error. Present in four places (`buycourses.ajax.php` twice,
`service_panel.php`, `panel.ajax.php`); replaced with `Display::get_icon_path()`,
the actual core helper (also wired up as Twig's `icon` filter) used everywhere
else in Chamilo for this exact fallback.

Fix: the "Purchase history" table on `/my-services` now shows the purchase date and
time (previously date only), and the downloadable invoice now shows both the
purchase date and the invoice date side by side, since they can legitimately differ.

Fix: the "Purchase history" table on `/my-services` no longer offers a Receipt,
Invoice, or "Request invoice" link for a pending or cancelled sale — those only
make sense once the sale is actually completed. This is now also enforced at the
`receipt.php`/`invoice.php`/`request_invoice.php` endpoints themselves (not just
hidden from the list), via a completed-status check added to
`BuyCoursesPlugin::canUserAccessInvoice()` — so a bookmarked or guessed URL for a
sale that was never completed (or later cancelled) is rejected even for platform
admins, since a receipt/invoice for money never received doesn't make sense
regardless of who's asking.

Fix: subscription sale invoices no longer share the same internal identifier space
as course/session sale invoices. `plugin_buycourses_invoices.is_service` is now a
3-way discriminator (course/session, service, subscription) instead of a boolean;
previously a subscription sale and a course/session sale with the same numeric id
could resolve to the wrong invoice record.

Change: the student-facing menu entry (sidebar, logged-out top nav, and the small
badge at the top of `/my-services`) was renamed from "Buy courses" to "Shop", since
the plugin now also sells services and subscriptions, not just courses/sessions.
French, Spanish, German, and Dutch translations added; other locales fall back to
the English label until the normal translation pipeline runs. The plugin's own
admin-facing title ("Sell courses", used in the plugin settings page and legacy
admin breadcrumbs) was intentionally left as-is — out of scope, but inconsistent
with this new name if anyone wants to revisit it later.

Fix: garbled `?` character appearing in PDF invoice/receipt times (e.g.
"1:48?AM"). Root cause was in Chamilo core's PDF renderer (`PDF::content_to_pdf()`),
which restricts output to CP1252/WinAnsi fonts and can't render the narrow
no-break space some locales use before "AM"/"PM"; it's now normalized to a regular
non-break space before rendering.

Fix: the service options shown on the "Create a new course" page
(`/resources/courses/new`) displayed every language block of a multilingual
service description concatenated together, instead of just the visitor's
language. `BuyCoursesPlugin::getDisplayedServiceCourseCreationOptionsForUser()`
stripped the description's HTML tags directly, without first filtering it
through `filterServiceMultilingualHtml()` like the service catalog and service
information pages already did — now uses the existing
`filterServiceMultilingualPlainText()` helper, which applies that same
language filtering before stripping tags.

ACTION REQUIRED for installations updated from an earlier version: run the update
procedure (see below) so the new `buyer_type`, `invoice_requested`, and
`gateway_transaction_id` columns are added to the `plugin_buycourses_sale`,
`plugin_buycourses_service_sale`, and `plugin_buycourses_subscription_rel_sale`
tables, and the new `ai_course_features_json` column is added to
`plugin_buycourses_services`. Either load
[your-host]/plugin/BuyCourses/update.php in your browser as a platform
administrator, or run this SQL manually:
```sql
ALTER TABLE plugin_buycourses_sale ADD buyer_type TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_sale ADD invoice_requested TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_sale ADD gateway_transaction_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE plugin_buycourses_service_sale ADD buyer_type TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_service_sale ADD invoice_requested TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_service_sale ADD gateway_transaction_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE plugin_buycourses_subscription_rel_sale ADD buyer_type TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_subscription_rel_sale ADD invoice_requested TINYINT(1) DEFAULT 0;
ALTER TABLE plugin_buycourses_subscription_rel_sale ADD gateway_transaction_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE plugin_buycourses_services ADD ai_course_features_json LONGTEXT DEFAULT NULL;
```

v7.5 - 2026-06-12
====
Feature: per-country EU VAT now applies to course, session and subscription sales, not only
services. At checkout the buyer fills in the same VAT declaration (country, postcode,
individual/business, VAT number) already used for services; the sale then applies the correct
destination-country VAT rate, B2B reverse charge, or export exemption, and stores the full VAT
evidence. When no buyer country is declared, the previous flat global/product rate still
applies. The catalog preview price stays on the flat rate for all product types (unchanged) —
the per-country rate is computed at the point of sale.

ACTION REQUIRED for installations updated from an earlier version: run the update procedure
(see below) so the new VAT evidence columns are added to the `plugin_buycourses_sale` and
`plugin_buycourses_subscription_rel_sale` tables.

Fix: an empty tax rate on a service or a subscription can now be saved again, and is stored
as NULL so the item falls back to the global tax rate (as intended). Older installations
created the `tax_perc` column as NOT NULL on the services and subscription tables, which made
saving an empty tax rate fail with "Column 'tax_perc' cannot be null".

ACTION REQUIRED for installations updated from an earlier version: run the update procedure
so these columns are made nullable. Either load [your-host]/plugin/BuyCourses/update.php in
your browser as a platform administrator, or run this SQL manually:
```sql
ALTER TABLE plugin_buycourses_services MODIFY tax_perc int unsigned NULL;
ALTER TABLE plugin_buycourses_subscription MODIFY tax_perc int unsigned NULL;
```

Fix: when tax is restricted to a single product type ("Only sessions" / "Only courses"), the
catalog now applies tax to sessions consistently with what is actually charged at purchase.
Previously the displayed price for sessions and session subscriptions always used the
"courses" tax rule, so the shown tax could differ from the amount charged. No action required
(code-only fix); the common "All" setting was never affected.

v7.4 - 2022-04-28
====
Add subscriptions support.

If the plugin has already been installed, the update.php script must be executed (load plugin/BuyCourses/update.php in your browser) to update the database structure by adding the plugin_buycourses_subscription, plugin_buycourses_subscription_rel_sale, plugin_buycourses_subscription_period and plugin_buycourses_coupon_rel_subscription_sale.

v7.3 - 2022-04-28
====
Add Cecabank payments support.

If the plugin has already been installed, the update.php script must be executed (load plugin/BuyCourses/update.php in your browser) to update the structure of the tables in the database.

v7.2 - 2021-11-22
====
Add Stripe payments support.

If the plugin has already been installed, the update.php script must be executed (load plugin/BuyCourses/update.php in your browser) to update the structure of the tables in the database.

v7.1 - 2021-10-26
====
Fix install issue with DB field type.

v7.0 - 2021-08-12
====
Added support for discount coupons.
Added a better table view for the sales report.
Multiple fixes to navigation-related issues.

WARNING: Updating this plugin (or Chamilo) without going through the specific update procedure for this plugin will break your sales pages.

The file [your-host]/plugin/BuyCourses/update.php *MUST* be executed to update the structure of the tables
in the database.

v6.0 - 2020-11-29
====
Added support for purchase instructions e-mail customization (although this
does not support multiple languages at the moment).
This requires changes to the DB tables:
```sql
ALTER TABLE plugin_buycourses_global_config ADD COLUMN info_email_extra TEXT;
```

v5.0 - 2019-02-06
====

This version includes two additional modules (taxes and invoices),
which can be enabled from the configuration.

The file update.php must be executed to update the structure of the tables
 in the database.


v4.0 - 2017-04-25
====

This version includes the Culqi payment gateway v1.0 (now expired) and introduces
an additional option to show the Buy Courses tab to anonymous users.

To enable these features, if you have already installed this plugin on your
portal prior to this version, you will need to add the corresponding settings
to your settings_current table. No documentation is available at this time on
how to do that, so please check up the code. Sorry about that.


v3.0 - 2015-09-25
====

This version has been fixed and improved for Chamilo LMS 1.10.x.

- A new user interface for platform admins and users.
- Avoid data redundancy by adding courses/sessions to catalog
- The catalog of sessions can be configured to offer some courses or sessions
in a currency other than the others courses or sessions
- The sales have a status: Pending, Completed, Canceled
- The Peding Orders pages has been replaced by a Sales Report.
Allowing filter the sales by its status
- The plugin Registration page was removed. Instead the Chamilo LMS
registrarion page is used.
- Added the ability to record beneficiaries with the sale of courses/sessions

##Changes in database structure

The database structure has been changed totally. The previous database
structure was formed for the tables:

- `plugin_buy_course` The registered courses in the platform
- `plugin_buy_course_country` The list of countries with their currencies
- `plugin_buy_course_paypal` The PayPal account info
- `plugin_buy_course_sale` The sales of courses that were made
- `plugin_buy_course_temporal` The pending orders of courses that were made
- `plugin_buy_course_transfer` The bank accounts for transfers
- `plugin_buy_session` The registered courses in the platform
- `plugin_buy_session_course` The courses in sessions
- `plugin_buy_session_sale` The sales of session that were made
- `plugin_buy_session_temporary` The pending orders of session that were made

To avoid the data redundancy, the `plugin_buy_course`, `plugin_buy_session`
and `plugin_buy_session_course` tables were replaced for the
`plugin_buycourses_item` table. And the `plugin_buy_course_sale`,
`plugin_buy_course_temporal`, `plugin_buy_session_sale` and
`plugin_buy_session_temporary` tables were replaced for the
 `plugin_buycourses_item` table.

The __new database__ structure is formed for the tables:

- `plugin_buycourses_currency` The list of countries with their currencies
- `plugin_buycourses_item` The registered courses and sessions in the platform
- `plugin_buycourses_item_re_beneficiary` The beneficiaries users with the sale of courses
- `plugin_buycourses_paypal_account` The PayPal account info
- `plugin_buycourses_sale` The sales of courses and sessions that were made
- `plugin_buycourses_transfer` The bank accounts for transfers

---

v2.0 - 2014-10-14
=================
This version adds support for sales of sessions access.
A session can be purchased as soon as it is given a price, granted the current
date is either previous to the session start date, between the start and end,
or no date has been defined for the session.
Students are subscribed automatically once they have paid. There is no
intermediary step.
This version does not work (yet) with the session period defined by user
(a special feature introduced in Chamilo 1.9.10).

Upgrade procedure
-----------------
If you are working with this plugin since earlier versions, you will have to
look at the installer to *fix* your plugin tables (add a few fields).

v1.0 - 2014-06-30 (or something)
=================
This is the first release of the plugin, with only the PayPal payment method
in working state and only for courses.
