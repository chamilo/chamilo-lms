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
     - Start Webinar
     - End Webinar
     - Participant/Host joined webinar
     - Participant/Host left webinar
     - All Recordings have completed
     - Recording transcript files have completed

    Then click on Done then on Save and copy your Verification Token to the field below.
10. click on Continue

## Changelog

**v0.4**

Added signed attendance to allow you to configure an attendance sheet where participants register their signature. The
signed attendance functionality is similar to that found in the Exercise Signature plugin but does not reuse it.

## Meetings - Webinars

A **meeting** or **webinar** can be linked to a local **user** and/or a local **course**/**session**:

  * a meeting/webinar with a course is a _course meeting/webinar_;
  * a meeting/webinar with a user and no course is a _user meeting/webinar_;
  * a meeting/webinar with no course nor user is a _global meeting/webinar_.

A webinar only can be creadted when your Zoom account has a plan with the webinars feature.

## Registrants

A **registrant** is the registration of a local user to a meeting/webinar.

Users do not register themselves to meetings.

* They are registered to a course meeting/webinar by the course manager.
* They are registered to a user meeting/webinar by that user.
* They are registered automatically to the global meeting/webinar, when they enter it.

## Recordings

A **recording** is the list of files created during a past meeting/webinar instance.

Course meeting/webinar files can be copied to the course by the course manager.

# Required Zoom user account

Recordings and user registration are only available to paying Zoom customers.

For a non-paying Zoom user, this plugin still works but participants will join anonymously.

The user that starts the meeting/webinar will be identified as the Zoom account that is defined in the plugin. Socreate
a generic account that works for all the users that start meetings.

# Changelog

## v0.4

* The creation of webinars is now allowed.
* Allows you to use multiple accounts and subaccounts to create meetings/webinars

**Updating to v0.4 from v0.3**

Please, execute this queries in your database:

```sql
ALTER TABLE plugin_zoom_meeting
   ADD account_email       VARCHAR(255) DEFAULT NULL,
   ADD type                VARCHAR(255) NOT NULL,
   ADD webinar_schema_json LONGTEXT     DEFAULT NULL;

CREATE TABLE plugin_zoom_signature (
      id            INT AUTO_INCREMENT NOT NULL,
      registrant_id INT DEFAULT NULL,
      signature     LONGTEXT           NOT NULL,
      registered_at DATETIME           NOT NULL,
      UNIQUE INDEX UNIQ_D41895893304A716 (registrant_id),
      PRIMARY KEY (id)
   )
   DEFAULT CHARACTER SET utf8
   COLLATE `utf8_unicode_ci`
   ENGINE = InnoDB;

ALTER TABLE plugin_zoom_signature
   ADD CONSTRAINT FK_D41895893304A716 FOREIGN KEY (registrant_id) REFERENCES plugin_zoom_registrant (id);

ALTER TABLE plugin_zoom_meeting
   ADD sign_attendance           TINYINT(1) NOT NULL,
   ADD reason_to_sign_attendance LONGTEXT DEFAULT NULL;
```

# Contributing

Read README.code.md for an introduction to the plugin's code.
