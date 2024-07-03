Lti/Provider plugin
===

Version 1.0

> This plugin is meant to be later integrated into Chamilo (in a major version
release).

The LTI provider feature is only compatible with LTI 1.3 Advantage, and demonstrates the possibility to integrate tools or content from Chamilo into other LMS platforms.
In this case, Chamilo is used as provider , and this plugin allows a student inside a course to play in a breakout game with certain difficulty options (Deep Linkings) which is scored (Assigment and Grade Services) and compared with the other members of the course (NRP Services).

# Installation

*Prior to installing/uninstalling this plugin, you will need to make sure the src/Chamilo/PluginBundle/Entity folder is
temporarily writeable by the web server.*

1. Install the plugin from the Plugins page
2. Enable the plugin from the Lti Provider Plugin Settings page
3. Assign to the Administrator region (will appear on the management page)
4. Add the LTI connection details to try out the little demo app (Configuration page)
5. Configure the LMS platforms for registration and deployment

To be able to acces LTI content from a different domain in an iframe, the hosting provider will have to enable it by activating this configuration in the app/config/configuration.php file :
```
// Enable samesite:None parameter for session cookie.
// More info: https://www.chromium.org/updates/same-site
// Also: https://developers.google.com/search/blog/2020/01/get-ready-for-new-samesitenone-secure
$_configuration['security_session_cookie_samesite_none'] = true;
```

# DB tables

These tables are normally created during the activation of the plugin. They are mentioned here for practical purposes. 
Note: "kid" means "Key ID", not "child".

## v1.0
```sql
CREATE TABLE plugin_lti_provider_platform (
 id int NOT NULL AUTO_INCREMENT,
 issuer varchar(255) NOT NULL,
 client_id varchar(255) NOT NULL,
 kid int(255) NOT NULL,
 auth_login_url varchar(255) NOT NULL,
 auth_token_url varchar(255) NOT NULL,
 key_set_url varchar(255) NOT NULL,
 deployment_id varchar(255) NOT NULL,
 tool_provider varchar(255) NULL,
 PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

CREATE TABLE plugin_lti_provider_platform_key (
  id INT AUTO_INCREMENT NOT NULL,
  kid VARCHAR(255) NOT NULL,
  public_key LONGTEXT NOT NULL,
  private_key LONGTEXT NOT NULL,
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

CREATE TABLE plugin_lti_provider_result (
  id int(11) NOT NULL AUTO_INCREMENT,
  issuer longtext NOT NULL,
  user_id int(11) NOT NULL,
  client_uid int(11) NOT NULL,
  course_code varchar(40) NOT NULL,
  tool_id int(11) NOT NULL,
  tool_name varchar(255) NOT NULL,
  score double NOT NULL,
  progress int(11) NOT NULL,
  duration int(11) NOT NULL,
  start_date datetime NOT NULL,
  user_ip varchar(255) NOT NULL,
  lti_launch_id varchar(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

## v1.1
### Database changes
You need to execute this SQL query in your database after updating your Chamilo after version 1.11.18 if the plugin was already installed before.

```sql
ALTER TABLE plugin_lti_provider_result MODIFY client_uid varchar(255) NOT NULL;
```

