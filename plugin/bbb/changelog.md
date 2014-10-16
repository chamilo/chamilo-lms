version 2.2 - 2014-10-15
------------------------
Changes:
* Now uses a pseudo-random guid to avoid clashing conferences when several Chamilo portals use the same server. If you were using this plugin before, you will have to update the plugin_bbb_meeting table to "alter table plugin_bbb_meeting add column remote_id char(36);".
*

version 2.1
-----------
Released with: Chamilo LMS 1.9.8
Changes:
* now supports sessions (requires you to "alter table plugin_bbb_meeting add column session_id int default 0;")

version 2.0
-----------
(to be described)

version 1.0
-----------
Released with: Chamilo LMS 1.9.0
