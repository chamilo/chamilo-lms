This plugin adds Zoom meetings, user registration to meetings and meeting recordings.

> This plugin requires a Zoom account to manage meetings.

Once enabled, it will show as an additional course tool in all courses' homepage: teachers will be able to launch a
conference and student to join it.

## Configuration

The Zoom API uses JSON Web Tokens (JWT) to authenticate account-level access. JWT apps provide an API Key and Secret
required to authenticate with JWT. To get them, create a JWT App:

1. Log into your [Zoom profile page]()
2. Click on Advanced / Application Marketplace
3. Click on [Develop / build App](https://marketplace.zoom.us/develop/create)
4. Choose JWT / Create
5. Information: Fill in fields about your "App" (application and company names, contact name and email address)
6. Click on Continue
7. App Credentials: Copy your API Key and Secret to these fields below
8. Click on Continue
9. Feature: enable Event Subscriptions to add a new one with endpoint
   URL https://your.chamilo.url/plugin/zoom/endpoint.php and add these event types:

     - Start Meeting
     - End Meeting
     - Participant/Host joined meeting
     - Participant/Host left meeting
     - All Recordings have completed
     - Recording transcript files have completed
    
     Then click on Done then on Save and copy your Verification Token to the field below.
11. click on Continue

## Meetings

A **meeting** can be linked to a local **user** and/or a local **course**/**session**:

  * a meeting with a course is a _course meeting_;
  * a meeting with a user and no course is a _user meeting_;
  * a meeting with no course nor user is a _global meeting_.

## Registrants

A **registrant** is the registration of a local user to a meeting.

Users do not register themselves to meetings.

* They are registered to a course meeting by the course manager.
* They are registered to a user meeting by that user.
* They are registered automatically to the global meeting, when they enter it.

## Recordings

A **recording** is the list of files created during a past meeting instance.

Course meeting files can be copied to the course by the course manager.

# Required Zoom user account

Recordings and user registration are only available to paying Zoom customers.

For a non-paying Zoom user, this plugin still works but participants will join anonymously.

The user that starts the meeting will be identified as the Zoom account that is defined in the plugin. Socreate a generic account that works for all the users that start meetings.

# Contributing

Read README.code.md for an introduction to the plugin's code.
