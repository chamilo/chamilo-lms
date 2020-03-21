# MigrationMoodlePlugin

Allow migrate course contents and user progress from a Moodle platform.

> In development.

## Instructions

- Install the plugin
- Set the configuration (params to moodle DB connection and moodledata directory)
- Optionally, set the admin region

You can run the migration tasks from browser using the admin panel.
You must ejecute the tasks in the order indicated in the task list.

Also you can run all the migrations running `php plugin/migrationmoodle/run_cli.php`.
But if you want to run the migration with multiple url, then you will need edi `MigrationMoodlePlugin::getAccessUrlId`,
`MigrationMoodlePlugin::getMoodledataPath` methods to set your plugin settings.

# Notes

- Check if exists an index on `c_lp_item_view.status` on Chamilo DB.
  It for optimize the performance when executing the SQL query used in UserScormProgressLoader.
- It requires a Moodle DB with MySQL 8 or MariaDB 10.2.2 for some tasks (LessonPagesTask).
