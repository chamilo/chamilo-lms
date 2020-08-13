The Chamilo Zoom plugin class itself is defined in _plugin/zoom/lib/ZoomPlugin.php_

It manipulates both **remote Zoom server objects** and **local database entities**:

# Local database entities

The local entities map the remote objects to Chamilo courses/sessions and users.

They also maintain a cache of the matching remote objects.

_Entity/*Entity.php_ are the local database entity classes.

Doctrine entity manager repository classes are in _lib/*EntityRepository.php_.

# Remote Zoom server objets

_lib/API/*.php_ contains the Zoom API data structure definition classes,
based on Zoom's own API specification:

* https://marketplace.zoom.us/docs/api-reference/zoom-api
* https://marketplace.zoom.us/docs/api-reference/zoom-api/Zoom%20API.oas2.json

These classes provide methods to list, create, update and delete the remote objects.

# JWT Client

API class methods use a JWT client implemented in _lib/API/JWTClient.php_.

The plugin constructor initializes the JWT Client, giving it required API key and secret.

# Event notification handler

_endpoint.php_ is the Zoom API event notification web hook end point.

It handles notifications sent by Zoom servers on useful events :

* meeting start and end,
* registrant join and leave,
* recordings created and deleted

# Administrative interface

_admin.php_ is the administrative interface.
It lists all meetings and recordings.

# Course tool

_start.php_ is the **course** tool target:

* to the course teacher, it shows a course meeting management interface;
* to the course learners, it shows the list of scheduled course meetings.

# Home page's profile block (also on "My Courses" page)

This plugin can add 3 kinds of links to "profile block" :

1. _join_meeting.php?meetingId=…_ links to upcoming meetings accessible to the current user.
_join_meeting.php_ presents the meeting and shows a link to enter the meeting.
2. _user.php_ is the **user**'s own meeting management interface.
3. _global.php_ directs the user to _join_meeting.php_ with the **global** meeting.

# Meeting management page

_admin.php_, _start.php_ and _user.php_ link to _meeting.php_.

_meeting.php_ is the meeting management page, where one can manage

* the meeting properties,
* the list of its registrants and
* its recordings.