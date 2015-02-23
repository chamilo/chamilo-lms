Advanced subscription plugin for Chamilo LMS
=======================================
Plugin to manage the registration queue and communication to sessions
from an external website creating a queue to control session subscription
and sending emails to approve student subscription requests

# Requirements
Chamilo LMS 1.10.0 or greater

# Settings

These settings have to be configured in the Configuration screen for the plugin

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

This plugin uses the following hooks (defined since Chamilo LMS 1.10.0):

* HookAdminBlock
* HookWSRegistration
* HookNotificationContent
* HookNotificationTitle


# Web services

This plugin also enables new webservices that can be used from registration.soap.php

* HookAdvancedSubscription..WSSessionListInCategory
* HookAdvancedSubscription..WSSessionGetDetailsByUser
* HookAdvancedSubscription..WSListSessionsDetailsByCategory

See `/plugin/advanced_subscription/src/HookAdvancedSubscription.php` to check Web services inputs and outputs

# How does this plugin works?

After install, fill the required parameters (described above)
Use web services to communicate course session inscription from external website
This allows students to search course sessions and subscribe if they match
the requirements.

The normal process is:
* Student searches course session
* Student reads session info depending student data
* Student requests to be subscribed
* A confirmation email is sent to student
* An authorization email is sent to student's superior (STUDENT BOSS role) or admins (when there is no superior) who will accept or reject the student request
* When the superior accepts or rejects, an email will be sent to the student and superior (or admin), respectively
* To complete the subscription, the request must be validated and accepted by an admin