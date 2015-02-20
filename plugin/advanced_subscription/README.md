Advanced subscription plugin for Chamilo LMS
=======================================
Plugin for managing the registration queue and communication to sessions
from an external website creating a queue to control session subscription
and sending emails to approve student subscription request
# Requirements
Chamilo LMS 1.10 or greater

# Settings

Parameters    | Description
------------- |-------------
Webservice url | Url to external website to get user profile (SOAP)
Induction requirement | Checkbox to enable induction as requirement
Courses count limit | Number of times a student is allowed at most to course by year
Yearly hours limit | Teaching hours a student is allowed at most  to course by year
Yearly cost unit converter | The cost of a taxation unit value (TUV)
Yearly cost limit | Number of TUV student courses is allowed at most to cost by year
Year start date | Date (dd/mm) when the year limit is renewed
Minimum percentage profile | Minimum percentage required from external website profile

# Hooks

This plugin use the next hooks:

* HookAdminBlock
* HookWSRegistration
* HookNotificationContent
* HookNotificationTitle


# Web services

* HookAdvancedSubscription..WSSessionListInCategory
* HookAdvancedSubscription..WSSessionGetDetailsByUser
* HookAdvancedSubscription..WSListSessionsDetailsByCategory

See `/plugin/advanced_subscription/src/HookAdvancedSubscription.php` to check Web services inputs and outputs

# How plugin works?

After install plugin, fill the parameters needed (described above)
Use Web services to communicate course session inscription from external website
This allow to student to search course session and subscribe if is qualified
and allowed to subscribe.
The normal process is:
* Student search course session
* Student read session info depending student data
* Student request a subscription
* A confirmation email is send to student
* An email is send to users (superior or admins) who will accept or reject student request
* When the user aceept o reject, an email will be send to student, superior or admins respectively
* To complete the subscription, the request must be validated and accepted by an admin