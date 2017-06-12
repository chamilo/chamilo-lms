BigBlueButton Chamilo plugin
============================
This plugin allows you to have videoconference rooms in each course.
It requires you to have a BigBlueButton videoconference server installed on another server (ideally).
Check www.bigbluebutton.org for more about BigBlueButton.

## Migrating to Chamilo LMS 1.10.x
For Chamilo 1.10.x, the Videoconference plugin has two new settings options: *Enable global conference* and *Enable conference in course groups*.

##### Database changes
You need execute these SQL queries in your database after making the migration process from 1.9.x.

```sql
ALTER TABLE plugin_bbb_meeting ADD voice_bridge int NOT NULL DEFAULT 1;
ALTER TABLE plugin_bbb_meeting ADD group_id int unsigned NOT NULL DEFAULT 0;
```
## Migrating to Chamilo LMS 1.11.x
For Chamilo 1.11.x, Videoconference plugin has two new settings options: 

##### Database changes
You need execute this SQL query in your database after making the Chamilo migration process from 1.10.x.
> If you are migrating from 1.9.x versions, you need execute the SQL queries from the migration to 1.10.x before.

```sql
ALTER TABLE plugin_bbb_meeting ADD user_id int unsigned NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD access_url int NOT NULL DEFAULT 1;
```
For version 2.5 you need execute these SQL queries
```sql
CREATE TABLE plugin_bbb_room (
    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    meeting_id int(10) unsigned NOT NULL,
    participant_id int(11) NOT NULL,
    in_at datetime NOT NULL,
    out_at datetime NOT NULL,
    FOREIGN KEY (meeting_id) REFERENCES plugin_bbb_meeting (id),
    FOREIGN KEY (participant_id) REFERENCES user (id)
);
ALTER TABLE plugin_bbb_meeting ADD video_url TEXT NULL;
ALTER TABLE plugin_bbb_meeting ADD has_video_m4v TINYINT NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD COLUMN user_id INT DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD COLUMN access_url INT DEFAULT 0;
```
For version 2.6 (adding limits) you need execute these SQL queries
```sql
INSERT INTO chamilo111x.settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('bbb_max_users_limit', 'bbb', 'setting', 'Plugins', '3', 'bbb', null, null, null, 1, 1, 0);
INSERT INTO chamilo111x.extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES (2, 15, 'plugin_bbb_course_users_limit', 'MaxUsersInConferenceRoom', '0', 1, 1, 0, 1, null, '2017-05-28 01:19:32');
INSERT INTO chamilo111x.extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES (3, 15, 'plugin_bbb_session_users_limit', 'MaxUsersInConferenceRoom', null, 1, 1, 0, 1, null, '2017-05-28 01:19:32');
```
