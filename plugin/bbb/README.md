BigBlueButton Chamilo plugin
============================
This plugin allows you to have videoconference rooms in each course.
It requires you to have a BigBlueButton videoconference server installed on another server (ideally).
Check www.bigbluebutton.org for more about BigBlueButton.

## Migrating from Chamilo LMS 1.9.x to 1.10.x
For Chamilo 1.10.x, the Videoconference plugin has two new settings options: *Enable global conference* and *Enable conference in course groups*.

##### Database changes
You need execute this SQL query en your database after making the migration process from 1.9.x.

```sql
ALTER TABLE plugin_bbb_meeting ADD voice_bridge int NOT NULL DEFAULT 1;
ALTER TABLE plugin_bbb_meeting ADD group_id int unsigned NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD user_id int unsigned NOT NULL DEFAULT 0;
ALTER TABLE plugin_bbb_meeting ADD access_url int NOT NULL DEFAULT 1;
```
