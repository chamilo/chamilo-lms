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
For the version 2.5 you need execute these SQL queries
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
```
