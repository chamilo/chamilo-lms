PauseTraining
=============

Overview
--------
This plugin lets users define a training pause period from their profile and allows
the platform to send inactivity reminders through a cron task.

What it does
------------
- Creates 4 user extra fields:
    - pause_formation
    - start_pause_date
    - end_pause_date
    - disable_emails
- Adds plugin settings to enable the feature, configure inactivity day thresholds,
  allow users to edit their pause settings, and define the sender user for notifications.
- Exposes the pause settings in the user profile form.
- Sends inactivity reminders when the cron script finds users whose last activity
  matches the configured day windows.
- Skips reminders when:
    - the user disabled automatic emails, or
    - the user is currently inside an active pause period.

Profile fields
--------------
When the plugin is enabled and the option to let users edit their pause settings
is active, the user profile form shows:

- Pause training
- Start pause date
- End pause date
- Disable automatic emails

These values are stored as user extra fields and are used by the cron task.

Installation
------------
1. Enable the plugin from the plugins list.
2. Click Install/Configure for the plugin.
3. Set the plugin as enabled.
4. Set a valid sender user for notifications.
5. Set the inactivity day thresholds with values like:
    - 5
    - 5,10,15
6. Make sure the plugin installation creates the user extra fields.

Configuration
-------------
The plugin provides these main settings:

- Enable plugin
  Turns the feature on or off.

- Allow users to edit pause training
  Displays the pause settings in the user profile form.

- Alert users after these inactivity days
  Defines the inactivity thresholds processed by the cron task.

- User that will send cron notifications
  Defines the Chamilo user used as sender for inactivity reminders.

Cron
----
Use the following scripts:

- `plugin/PauseTraining/cron.php`
  Runs the real inactivity reminder process.

- `plugin/PauseTraining/cronTest.php`
  Simulates multiple dates and prints debug-style output without sending messages.

Cron behavior
-------------
The cron task:

1. Collects active platform users.
2. Detects their latest activity using login and course access tracking data.
3. Compares activity dates against the configured inactivity windows.
4. Skips users who:
    - disabled automatic emails, or
    - have an active pause period at the evaluated date.
5. Queues and sends reminder messages for the remaining users.

Testing checklist
-----------------
Recommended checks after installation:

1. Open the user profile form.
2. Set:
    - Pause training
    - Start pause date
    - End pause date
    - Disable automatic emails
3. Save and confirm the values are stored correctly.
4. Run `cronTest.php` and verify:
    - users with `disable_emails = 1` are skipped,
    - users with an active pause window are skipped,
    - inactive users without pause are added to the message queue.
5. Run `cron.php` from the scheduler for the real process.

Notes
-----
- The cron test script does not send messages; it only prints what would happen.
- The real cron script sends messages only when matching inactivity windows are found.
- The pause period must include both start and end dates to be considered active.
