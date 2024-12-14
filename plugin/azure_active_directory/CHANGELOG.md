# Azure Active Directory Changelog

## 2.5 - 2024-11-18

* Added new options to get the user and groups with delta query (or change tracking) when syncing with scripts.
this requires manually doing the following changes to  your database if you are upgrading from v2.4
```sql
CREATE TABLE azure_ad_sync_state (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
```

## 2.4 - 2024-08-28

* Added a new user extra field to save the unique Azure ID (internal UID).
This requires manually doing the following changes to your database if you are upgrading from v2.3
```sql
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES (1, 1, 'azure_uid', 'Azure UID (internal ID)', '', 1, null, null, null, null, '2024-08-28 00:00:00');
```
* Added a new option to set the order to verify the existing user in Chamilo
```sql
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_existing_user_verification_order', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', '', '', 1, 1, 0);
```
* Added a new option to update user info during the login proccess.
```sql
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_update_users', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', '', '', 1, 1, 0);
```
* Added new scripts to syncronize users and groups with users and usergroups (classes). And an option to deactivate accounts in Chamilo that do not exist in Azure.
```sql
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_tenant_id', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', '', '', 1, 1, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_deactivate_nonexisting_users', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', '', '', 1, 1, 0);
```

## 2.3 - 2021-03-30

* Added admin, session admin and teacher groups. This requires adding the following fields to your database if 
  upgrading from a previous version of the plugin manually:
```
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_group_id_admin', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', null, null, 1, 1, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_group_id_session_admin', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', null, null, 1, 1, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('azure_active_directory_group_id_teacher', 'azure_active_directory', 'setting', 'Plugins', '', 'azure_active_directory', '', null, null, 1, 1, 0);
```

## 2.2 - 2021-03-02

* Added provisioning setting

## 2.1 - 2020

* Initial tested implementation of Azure Active Directory single sign on