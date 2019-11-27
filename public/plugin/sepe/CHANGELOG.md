v2.1 - 2018-04-05
=================
- Fix: SEPE plugin foreign key constraint fails [#2461](https://github.com/chamilo/chamilo-lms/issues/2461)

##### Database changes
You need execute these SQL queries in your database.

```sql
ALTER TABLE plugin_sepe_participants MODIFY company_tutor_id INT( 10 ) UNSIGNED NULL;
ALTER TABLE plugin_sepe_participants MODIFY training_tutor_id INT( 10 ) UNSIGNED NULL;
```

v2.0 - 2017-05-23
=================
This version has been fixed and improved for Chamilo LMS 1.11.x.

Upgrade procedure
-----------------
If you are working with this plugin since earlier versions, you will have to
look at the installer to *fix* your plugin tables (add a few fields).

http://*yourdominio.com*/**plugin/sepe/update.php**

v1.0 - 2016-11-14
=================
This is the first release of the plugin, valid for Chamilo LMS 1.10.x
