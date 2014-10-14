v2.0 - 2014-10-14
=================
This version adds support for sales of sessions access.
A session can be purchased as soon as it is given a price, granted the current
date is either previous to the session start date, between the start and end, or
no date has been defined for the session.
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
This is the first release of the plugin, with only the PayPal payment method in working state and only for courses.
