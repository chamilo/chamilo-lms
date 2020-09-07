<?php
/* License: see /license.txt */

// Needed in order to show the plugin title
$strings['plugin_title'] = "Zoom Videoconference";
$strings['plugin_comment'] = "Zoom Videoconference integration in courses and sessions";

$strings['tool_enable'] = 'Zoom videoconference tool enabled';
$strings['apiKey'] = 'API Key';
$strings['apiSecret'] = 'API Secret';
$strings['verificationToken'] = 'Verification Token';
$strings['enableParticipantRegistration'] = 'Enable participant registration';
$strings['enableCloudRecording'] = 'Automatic recording type';
$strings['enableGlobalConference'] = 'Enable global conference';
$strings['enableGlobalConferencePerUser'] = 'Enable global conference per user';
$strings['globalConferenceAllowRoles'] = "Global conference link only visible for these user roles";
$strings['globalConferencePerUserAllowRoles'] = "Global conference per user link only visible for these user roles";

$strings['tool_enable_help'] = "Choose whether you want to enable the Zoom videoconference tool.
Once enabled, it will show as an additional course tool in all courses' homepage :
teachers will be able to <strong>launch</strong> a conference and student to <strong>join</strong> it.
<br/>
This plugin requires a Zoom account to manage meetings.
The Zoom API uses JSON Web Tokens (JWT) to authenticate account-level access.
<br/>
JWT apps provide an <strong>API <em>Key</em> and <em>Secret</em></strong> required to authenticate with JWT.

To get them, create a <em>JWT App</em> :
<br/>1. log into <a href=\"https://zoom.us/profile\">your Zoom profile page</a>
<br/>2. click on <em>Advanced / Application Marketplace</em>
<br/>3. click on <em><a href=\"https://marketplace.zoom.us/develop/create\">Develop / build App</a></em>
<br/>4. choose <em>JWT / Create</em>
<br/>5. Information: fill in fields about your \"App\"
(application and company names, contact name and email address)
<br/>6. Click on <em>Continue</em>
<br/>7. App Credentials: <strong>copy your API Key and Secret to these fields below</strong>
<br/>8. click on <em>Continue</em>
<br/>9. Feature:
enable <em>Event Subscriptions</em> to add a new one with endpoint URL
<code>https://your.chamilo.url/plugin/zoom/endpoint.php</code>
and add these event types:
<br/>- Start Meeting
<br/>- End Meeting
<br/>- Participant/Host joined meeting
<br/>- Participant/Host left meeting
<br/>- All Recordings have completed
<br/>- Recording transcript files have completed
<br/>then click on <em>Done</em> then on <em>Save</em>
and <strong>copy your Verification Token to the field below</strong>.
<br/>10. click on <em>Continue</em>
<br/>
<strong>Attention</strong>:
<br/>Zoom is <em>NOT</em> free software and specific rules apply to personal data protection.
Please check with Zoom and make sure they satisfy you and learning users.";

$strings['enableParticipantRegistration_help'] = "Requires a paying Zoom profile.
Will not work for a <em>basic</em> profile.";

$strings['enableCloudRecording_help'] = "Requires a paying Zoom profile.
Will not work for a <em>basic</em> profile.";

// please keep these lines alphabetically sorted
$strings['AllCourseUsersWereRegistered'] = "All course students were registered";
$strings['Agenda'] = "Agenda";
$strings['CannotRegisterWithoutEmailAddress'] = "Cannot register without email address";
$strings['CopyingJoinURL'] = "Copying join URL";
$strings['CopyJoinAsURL'] = "Copy 'join as' URL";
$strings['CopyToCourse'] = "Copy to course";
$strings['CouldNotCopyJoinURL'] = "Could not copy join URL";
$strings['Course'] = "Cours";
$strings['CreatedAt'] = "Created at";
$strings['CreateLinkInCourse'] = "Create link(s) in course";
$strings['CreateUserVideoConference'] = "Create user conference";
$strings['DateMeetingTitle'] = "%s: %s";
$strings['DeleteMeeting'] = "Delete meeting";
$strings['DeleteFile'] = "Delete file(s)";
$strings['Details'] = "Details";
$strings['DoIt'] = "Do it";
$strings['Duration'] = "Duration";
$strings['DurationFormat'] = "%hh%I";
$strings['DurationInMinutes'] = "Duration (in minutes)";
$strings['EndDate'] = "End Date";
$strings['EnterMeeting'] = "Enter meeting";
$strings['ViewMeeting'] = "View meeting";
$strings['Files'] = "Files";
$strings['Finished'] = "finished";
$strings['FileWasCopiedToCourse'] = "The file was copied to the course";
$strings['FileWasDeleted'] = "The file was deleted";
$strings['GlobalMeeting'] = "Global conference";
$strings['GlobalMeetingPerUser'] = "Global conference per user";
$strings['GroupUsersWereRegistered'] = "Group members were registered";
$strings['InstantMeeting'] = "Instant meeting";
$strings['Join'] = "Join";
$strings['JoinGlobalVideoConference'] = "Join global conference";
$strings['JoinURLCopied'] = "Join URL copied";
$strings['JoinURLToSendToParticipants'] = "Join URL to send to participants";
$strings['LiveMeetings'] = "Live meetings";
$strings['LinkToFileWasCreatedInCourse'] = "A link to the file was added to the course";
$strings['MeetingDeleted'] = "Meeting deleted";
$strings['MeetingsFound'] = "Meetings found";
$strings['MeetingUpdated'] = "Meeting updated";
$strings['NewMeetingCreated'] = "New meeting created";
$strings['Password'] = "Password";
$strings['RecurringWithFixedTime'] = "Recurring with fixed time";
$strings['RecurringWithNoFixedTime'] = "Recurring with no fixed time";
$strings['RegisterAllCourseUsers'] = "Register all course users";
$strings['RegisteredUserListWasUpdated'] = "Registered user list updated";
$strings['RegisteredUsers'] = "Registered users";
$strings['RegisterNoUser'] = "Register no user";
$strings['RegisterTheseGroupMembers'] = "Register these group members";
$strings['ScheduleAMeeting'] = "Schedule a meeting";
$strings['ScheduledMeeting'] = "Scheduled meeting";
$strings['ScheduledMeetings'] = "Scheduled Meetings";
$strings['ScheduleAMeeting'] = "Schedule a meeting";
$strings['SearchMeeting'] = "Search meeting";
$strings['Session'] = "Session";
$strings['StartDate'] = "Start Date";
$strings['Started'] = "started";
$strings['StartInstantMeeting'] = "Start instant meeting";
$strings['StartMeeting'] = "Start meeting";
$strings['StartTime'] = "Start time";
$strings['Topic'] = "Topic";
$strings['TopicAndAgenda'] = "Topic and agenda";
$strings['Type'] = "Type";
$strings['UpcomingMeetings'] = "Upcoming meetings";
$strings['UpdateMeeting'] = "Update meeting";
$strings['UpdateRegisteredUserList'] = "Update registered user list";
$strings['UserRegistration'] = "User registration";
$strings['Y-m-d H:i'] = "Y-m-d H:i";
$strings['Waiting'] = "waiting";
$strings['XRecordingOfMeetingXFromXDurationXDotX'] = "%s recording of meeting %s from %s (%s).%s";
$strings['YouAreNotRegisteredToThisMeeting'] = "You are not registered to this meeting";
$strings['ZoomVideoConferences'] = "Zoom Video Conferences";
$strings['Recordings'] = "Recordings";
$strings['CreateGlobalVideoConference'] = "Create global video conference";
$strings['ConferenceNotStarted'] = "Conference not started";
$strings['MeetingNotFound'] = "Meeting not found";
$strings['JoinURLNotAvailable'] = "URL not available";
$strings['Meetings'] = "Meetings";
$strings['ConferenceType'] = "Conference type";
$strings['ForEveryone'] = "Everyone";
$strings['SomeUsers'] = "Some users (Select later)";
$strings['Activity'] = "Activity";
$strings['ConferenceNotAvailable'] = "Conference not available";
