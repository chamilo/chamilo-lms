Lti/Provider plugin
===

Version 1.0

> This plugin is meant to be later integrated into Chamilo (in a major version
release).

Lti provider is compatible only for Lti 1.3 Advance, defines the possibility to integrate tools or content into Platforms LMS.
In this case Chamilo is used as provider , this plugin allows a student inside a course to play in a breakout game with with certain difficulty options (Deep Linkings) which is scored (Assigment and Grade Services) and comparing with the members of the course (NRP Services).

# Installation

1. Install the plugin from the Plugins page
2. Enable the plugin from the Lti Provider Plugin Settings page
3. Assign to the Administrator region (in the regions management page)
4. Add the LTI connection details to try out this app (Configure page)
5. To configure the Platforms LMS for registration and deployment

# DB tables

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
