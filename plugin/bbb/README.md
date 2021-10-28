BigBlueButton Chamilo plugin
============================
This plugin allows you to have videoconference rooms in each course.
It requires you to have a BigBlueButton videoconference server installed on another server (ideally).
Check www.bigbluebutton.org for more about BigBlueButton.

## Migrating to Chamilo LMS 1.10.x
For Chamilo 1.10.x, the Videoconference plugin has two new settings options: 
*Enable global conference* and *Enable conference in course groups*.

##### Database changes
You need execute these SQL queries in your database after making the migration process from 1.9.x.

```sql
ALTER TABLE plugin_bbb_meeting ADD voice_bridge int NOT NULL DEFAULT 1;
ALTER TABLE plugin_bbb_meeting ADD group_id int unsigned NOT NULL DEFAULT 0;
```
## Migrating to Chamilo LMS 1.11.x
For Chamilo 1.11.x, Videoconference plugin has one new setting option: 
*Disable Course Settings*. 

##### Database changes
You need execute this SQL query in your database after making the Chamilo migration process from 1.10.x.
> If you are migrating from 1.9.x versions, you need execute the SQL queries from the migration to 1.10.x before.

```sql
ALTER TABLE plugin_bbb_meeting ADD user_id int unsigned NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD access_url int NOT NULL DEFAULT 1;
```
For version 2.5 you need execute these SQL queries
```sql
CREATE TABLE IF NOT EXISTS plugin_bbb_room (
    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    meeting_id int(10) unsigned NOT NULL,
    participant_id int(11) NOT NULL,
    in_at datetime NOT NULL,
    out_at datetime NOT NULL
);
ALTER TABLE plugin_bbb_meeting ADD COLUMN video_url TEXT NULL;
ALTER TABLE plugin_bbb_meeting ADD COLUMN has_video_m4v TINYINT NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD COLUMN user_id INT DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD COLUMN access_url INT DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD COLUMN remote_id char(30);
ALTER TABLE plugin_bbb_meeting ADD COLUMN visibility TINYINT NOT NULL DEFAULT 1;
ALTER TABLE plugin_bbb_meeting ADD COLUMN session_id INT DEFAULT 0;
```
For version 2.6 (adding limits) you need execute these SQL queries
```sql
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('bbb_max_users_limit', 'bbb', 'setting', 'Plugins', '3', 'bbb', null, null, null, 1, 1, 0);
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES (2, 15, 'plugin_bbb_course_users_limit', 'MaxUsersInConferenceRoom', '0', 1, 1, 0, 1, null, '2017-05-28 01:19:32');
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES (3, 15, 'plugin_bbb_session_users_limit', 'MaxUsersInConferenceRoom', null, 1, 1, 0, 1, null, '2017-05-28 01:19:32');
```

For version 2.7
```sql
ALTER TABLE plugin_bbb_meeting ADD COLUMN interface INT NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_room ADD COLUMN interface INT NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_room MODIFY COLUMN in_at datetime;
ALTER TABLE plugin_bbb_room MODIFY COLUMN out_at datetime;
```

For version 2.8
```sql
ALTER TABLE plugin_bbb_meeting ADD COLUMN internal_meeting_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE plugin_bbb_room ADD close INT NOT NULL DEFAULT 0;
```

For version 2.9 (Optional, requires an update version of BBB)

```sql
ALTER TABLE plugin_bbb_room DROP COLUMN interface;
ALTER TABLE plugin_bbb_meeting DROP COLUMN interface;
```

For version 2.10 (Handles multiple recording formats - Check https://github.com/chamilo/chamilo-lms/issues/3703)

```sql
CREATE TABLE plugin_bbb_meeting_format (
	id int unsigned not null PRIMARY KEY AUTO_INCREMENT,
	meeting_id int unsigned not null,
	format_type varchar(255) not null,
	resource_url text not null
)
```

## Improve access tracking in BBB
You need to configure the cron using the *cron_close_meeting.php* file.

# Digital ocean VM

In order to use DigitalOceanVM classes a new package is required:
  
```
composer requires toin0u/digitalocean
``` 