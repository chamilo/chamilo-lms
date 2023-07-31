<?php
/* License: see /license.txt */

// Needed in order to show the plugin title
$strings['plugin_title'] = "Zoom Videoconference";
$strings['plugin_comment'] = "Zoom Videoconference integration in courses and sessions";

$strings['tool_enable'] = 'Zoom videoconference tool enabled';
$strings['apiKey'] = 'API Key';
$strings['apiKey_help'] = 'For a JWT application type (<small>this app type will be deprecated on 6/1/2023</small>)';
$strings['apiSecret'] = 'API Secret';
$strings['apiSecret_help'] = 'For a JWT application type (<small>this app type will be deprecated on 6/1/2023</small>)';
$strings['verificationToken'] = 'Verification Token';
$strings['verificationToken_help'] = 'For a JWT application type (<small>this app type will be deprecated on 6/1/2023</small>)';
$strings[ZoomPlugin::SETTING_ACCOUNT_ID] = 'Account ID';
$strings[ZoomPlugin::SETTING_ACCOUNT_ID.'_help'] = 'For a Server-to-Server OAuth application type';
$strings[ZoomPlugin::SETTING_CLIENT_ID] = 'Client ID';
$strings[ZoomPlugin::SETTING_CLIENT_ID.'_help'] = 'For a Server-to-Server OAuth application type';
$strings[ZoomPlugin::SETTING_CLIENT_SECRET] = 'Client secret';
$strings[ZoomPlugin::SETTING_CLIENT_SECRET.'_help'] = 'For a Server-to-Server OAuth application type';
$strings[ZoomPlugin::SETTING_SECRET_TOKEN] = 'Secret token';
$strings[ZoomPlugin::SETTING_SECRET_TOKEN.'_help'] = 'For a Server-to-Server OAuth application type';
$strings['enableParticipantRegistration'] = 'Enable participant registration';
$strings['enableCloudRecording'] = 'Automatic recording type';
$strings['enableGlobalConference'] = 'Enable global conference';
$strings['enableGlobalConferencePerUser'] = 'Enable global conference per user';
$strings['globalConferenceAllowRoles'] = "Global conference link only visible for these user roles";
$strings['globalConferencePerUserAllowRoles'] = "Global conference per user link only visible for these user roles";
$strings['accountSelector'] = 'Account selector';
$strings['accountSelector_help'] = 'It allows you to declare the emails of the different accounts with whom you want to open the Zoom videos. Separated by semicolons (account_one@example.come;account_two@exaple.com).';

$strings['tool_enable_help'] = "Choose whether you want to enable the Zoom videoconference tool.
Once enabled, it will show as an additional course tool in all courses' homepage :
teachers will be able to <strong>launch</strong> a conference and student to <strong>join</strong> it.
<br/>
This plugin requires a Zoom account to manage meetings.
<p>The Zoom API uses JSON Web Tokens (JWT) to authenticate account-level access. To get them, create a JWT App or a Server-to-Sever OAuth app:</p>
<blockquote>
  <p>From June 1, 2023, Zoom recommend that you create a Server-to-Server OAuth application to replace the funcionality of
  a JWT app in your account.</p>
</blockquote>
<ol>
<li>Log into your <a href=\"https://zoom.us/profile\">Zoom profile page</a></li>
<li>Click on Advanced / Application Marketplace</li>
<li>Click on <a href=\"https://marketplace.zoom.us/develop/create\">Develop / Build App</a></li>
<li>Choose JWT or Server-to-Serve OAuth and then Create</li>
<li>Information: Fill in fields about your \"App\" (application and company names, contact name and email address)</li>
<li>Click on Continue</li>
<li>App Credentials
<ol>
<li>For a JWT application: Copy your API Key and Secret to the plugin configuration</li>
<li>For a Server-to-Server OAuth application: Copy your <em>Account ID</em>, <em>Client ID</em> and <em>Client secret</em> to the plugin
configuration</li>
</ol></li>
<li>Click on Continue</li>
<li><p>Feature: enable <em>Event Subscriptions</em> to add a new one with endpoint URL
<code>https://your.chamilo.url/plugin/zoom/endpoint.php</code> (validate the endpoint to allow to activate the app) and add
these event types:</p>
<ul>
<li>Start Meeting</li>
<li>End Meeting</li>
<li>Participant/Host joined meeting</li>
<li>Participant/Host left meeting</li>
<li>Start Webinar</li>
<li>End Webinar</li>
<li>Participant/Host joined webinar</li>
<li>Participant/Host left webinar</li>
<li>All Recordings have completed</li>
<li>Recording transcript files have completed</li>
</ul>
<p>Then click on Done then on Save and copy your <em>Verification Token</em> if you have a JWT application or the <em>Secret
Token</em> if you have an Server-to-Server OAuth application to the plugin configuration</p></li>
<li>click on Continue</li>
<li>Scopes (only for Server-to-Server OAuth application): Click on <em>Add Scopes</em> and select <em>meeting:write:admin</em>,
<em>webinar:write:admin</em>, <em>recording:write:admin</em>. Then click on Done.</li>
</ol>
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
$strings['SignAttendance'] = "Sign attendance";
$strings['ReasonToSign'] = 'Reason to sign attendance';
$strings['ConferenceWithAttendance'] = "Conference with attendance sign";
$strings['Sign'] = "Sign";
$strings['Signature'] = "Signature";
$strings['Meeting'] = "Meeting";
$strings['Webinar'] = "Webinar";
$strings['AudienceType'] = 'Audience type';
$strings['AccountEmail'] = 'Account email';
$strings['NewWebinarCreated'] = "New webinar created";
$strings['UpdateWebinar'] = 'Update webinar';
$strings['WebinarUpdated'] = "Webinar updated";
$strings['DeleteWebinar'] = "Delete webinar";
$strings['WebinarDeleted'] = "Webinar deleted";
$strings['UrlForSelfRegistration'] = "URL for self registration";
$strings['RegisterMeToConference'] = "Register me to conference";
$strings['UnregisterMeToConference'] = "Unregister me to conference";
