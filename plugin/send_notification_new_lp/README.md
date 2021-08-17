Mailing new LPs to students and their HR Managers
======

This plugin allows you to enable sending of emails to students and their human resources managers at the moment an
LP is published.
When activating this plugin it adds a checkbox in the LP's configuration page to define is the LP should be notified to the learners

For its operation, it is necessary that there is an extra field notify_student_and_hrm_when_available with default_value
equal to 1, which will enable the possibility of executing the main/cron/learning_path_reminder.php cron to send
the emails to the users registered in the lp and to their hr.

It is recommended that it be run once a day since it evaluates all lp's in the range from 0:00 to 23:59.
