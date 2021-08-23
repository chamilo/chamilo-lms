Lti/Provider plugin
===

Version 1.0

> This plugin is meant to be later integrated into Chamilo (in a major version
release).

The LTI provider feature is only compatible with LTI 1.3 Advantage, and demonstrates the possibility to integrate tools or content from Chamilo into other LMS platforms.
In this case, Chamilo is used as provider , and this plugin allows a student inside a course to play in a breakout game with certain difficulty options (Deep Linkings) which is scored (Assigment and Grade Services) and compared with the other members of the course (NRP Services).

# Installation

1. Install the plugin from the Plugins page
2. Enable the plugin from the Lti Provider Plugin Settings page
3. Assign to the Administrator region (will appear on the management page)
4. Add the LTI connection details to try out the little demo app (Configuration page)
5. Configure the LMS platforms for registration and deployment

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
```
