Version 2.5 - 2016-07
---------------------
* User global conference support
   * Requires a database change: "ALTER TABLE plugin_bbb_meeting ADD COLUMN user_id INT DEFAULT 0"
   * Requires a database change: "ALTER TABLE plugin_bbb_meeting ADD COLUMN access_url INT DEFAULT 0"
   
Version 2.4 - 2016-05
------------------------
Changes:

* Global conference support (Requires to update the plugin settings).
* Course group conference support
   * Requires a database change: "ALTER TABLE plugin_bbb_meeting ADD COLUMN group_id INT DEFAULT 0"
   * Requires to update the plugin settings.

Version 2.3 - 2015-05-18
------------------------
Changes:
* Added support for variable voiceBridge to be sent on meeting creation. See https://code.google.com/p/bigbluebutton/issues/detail?can=2&start=0&num=100&q=&colspec=ID%20Type%20Status%20Priority%20Milestone%20Owner%20Component%20Summary&groupby=&sort=&id=1186 and https://support.chamilo.org/issues/7669 for details.
* Requires a database change: "ALTER TABLE plugin_bbb_meeting ADD COLUMN voice_bridge INT NOT NULL DEFAULT 1;"

Version 2.2 - 2014-10-15
------------------------
Changes:
* Add a pseudo-random guid to avoid clashing conferences when several Chamilo portals use the same server. If you were using this plugin before, you will have to update the plugin_bbb_meeting table to "alter table plugin_bbb_meeting add column remote_id char(36);".
* Add possibility to hide recordings of previous conferences from the list. If you were using this plugin before, you will have to update the plugin_bbb_meeting table to "alter table plugin_bbb_meeting add column visibility tinytint not null default 1;".
* Show action icons in the action column
* Hide the ID of the meeting (this was an internal ID, useless to the final user). It is still in the HTML source, however
* Show number of minutes of the recording (in the recordings list)

Version 2.1
-----------
Released with: Chamilo LMS 1.9.8
Changes:
* now supports sessions (requires you to "alter table plugin_bbb_meeting add column session_id int default 0;")

Version 2.0
-----------

Version 1.0
-----------
Released with: Chamilo LMS 1.9.0
