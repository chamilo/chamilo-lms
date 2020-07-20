# Zoom Plugin

The Chamilo Zoom plugin class itself is defined in _plugin/zoom/lib/zoom_plugin.class.php_

It manipulates both **remote Zoom server objects** and **local database entities**:
* Meetings
* Recordings
* Registrants

Local entities map the remote objects to Chamilo courses/sessions and users:
* a Registrant is a local User registered to a Meeting
* a Meeting can be linked to a local user and/or a local course/session:
  * a meeting with a course is a course meeting;
  * a meeting with a user and no course is a user meeting;
  * a meeting with no course nor user is a global meeting.

Local entities also cache the remote objects.

The local data is kept up-to-date by the notification webhook endpoint _endpoint.php_.

## Remote Zoom server objet manipulation

lib/API contains the Zoom API data structure definition classes.

These classes provide methods to list, create, update and delete the remote objects.

They do that using a client that sends requests to remote Zoom servers and downloads answers.

The client is implemented in lib/API/JWTClient.php.

The plugin constructor initializes the JWT Client, giving it required API key and secret.

## Local database entities

_Entity/*Entity.php_ are the local database entity classes.

Doctrine entity manager repository classes are in _lib/*EntityRepository.php_.

## Non-class code (entry points)

_admin.php_ is the administrative interface. It lists all meetings and recordings.

_start.php_ is the **course** tool target:
* to the course teacher, it shows a course meeting management interface;
* to the course learners, it shows the list of scheduled course meetings.

This plugin can add 3 kinds of links to the home page's "profile block" :
1. _join_meeting.php?meetingId=…_ links to upcoming meetings accessible to the current user.
_join_meeting.php_ presents the meeting and shows a link to enter the meeting.
2. _user.php_ is the **user**'s own meeting management interface.
3. _global.php_ directs the user to _join_meeting.php_ with the **global** meeting.

_admin.php_, _start.php_ and _user.php_ link to _meeting.php_.
_meeting.php_ is the meeting management page, where one can manage
* the meeting properties,
* the list of its registrants and
* its recordings.

_endpoint.php_ is the Zoom notification web hook API end point.
It handles notifications sent by Zoom servers on useful events :
* meeting start and end,
* registrant join and leave,
* recordings created and deleted