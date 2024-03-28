Check Extra Fields 'author' and 'company'
======

The "User by organization" report allows the administrator to select a 
date range to show the number of users who have been subscribed to a learning
path or a course during this time frame. The number of users are grouped by 
entity/company.

The "Learning path by author" report allows the administrator to define, for 
each user, if (s)he is an author or not. Then, for each item in a Learning 
Path, the administrator can select who is its author from the identified list
and indicate the cost of that item.

Finally, the reports allow the administrator to select a date range to show
for each author how many of his/her content (LP item) users have been given
access to (based on the learning path subscriptions by users) and show the 
amount of money they should be paid based on the number of accesses given
during this period.

This plugin adds the extra fields necessary to display the reports:

* The "User by organization" report requires the 'company' extra field to be created on user.
* The "Learning path by author" report requires the 'authors' extra field to be created on lp.
* The "LP Item by author" report additional reports requires the 'authorlpitem' extra field to be created on lp_item and the 'authorlp' extra field to be created on 'user'.
* For prices to be adequately shown, the 'price' extra field needs to be created on 'lp_item'.

## Uninstall

When uninstalling this plugin, the extra fields created will not be removed,
for data persistence reasons.