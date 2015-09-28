v3.0
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
