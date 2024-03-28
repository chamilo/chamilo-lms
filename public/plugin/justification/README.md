Justification
==============

1. Enable the plugin.
2. Create the justification files in plugin/justification/list.php


When activating the justification plugin it adds a link at the bottom of the plateform block of the administration page to open plugin/justification/list.php to create and manage the justification needed.
On this page the admin can manage the list of justification that would be asked to users, and select a course for automatic inscription if the justificatives are completed and desinscription if justicatives are not completed or outdated. For the automatic subscription and removal you need to create a cron to run plugin/justification/cron.php periodically to do the verification.
The justification plugin only activate a new tab in the profile for the user to upload some justification (the list of which are defined by the administrator on the page plugin/justification/list.php indicated above).
If the notification system is activated then you have the ability to create notification to be sent before the expiration of the justification if configured so.
To activate it you have to set it in app/config/configuration.php with :
```
// Show notification events
/*CREATE TABLE IF NOT EXISTS notification_event (
id INT unsigned NOT NULL auto_increment PRIMARY KEY,
        title VARCHAR(255),
        content TEXT,
        link TEXT,
        persistent INT,
        day_diff INT,
        event_type VARCHAR(255)
    );
ALTER TABLE notification_event ADD COLUMN event_id INT NULL;
CREATE TABLE IF NOT EXISTS notification_event_rel_user (
    id INT UNSIGNED AUTO_INCREMENT NOT NULL,
    event_id INT unsigned,
    user_id INT,
    INDEX FK_EVENT (event_id),
    INDEX FK_USER (user_id),
    PRIMARY KEY (id)
);
ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_EVENT FOREIGN KEY (event_id) REFERENCES notification_event (id) ON DELETE CASCADE;
ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
*/
// create new user text extra field called 'notification_event' to save the persistent settings.
// $_configuration['notification_event'] = false;
```

