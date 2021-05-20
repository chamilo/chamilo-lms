# Azure Active Directory Changelog

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