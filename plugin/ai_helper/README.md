AI Helper plugin
======

Version 1.1

> This plugin is meant to be later integrated into Chamilo (in a major version
release).

The AI helper plugin integrates into parts of the platform that seem the most useful to teachers/trainers or learners. 
Because available Artificial Intelligence (to use the broad term) now allows us to ask for meaningful texts to be generated, we can use those systems to pre-generate content, then let the teacher/trainer review the content before publication.

Currently, this plugin is only integrated into:

 - exercises: in the Aiken import form, scrolling down
 - learnpaths: option to create one with openai

### OpenAI/ChatGPT

The plugin, created in early 2023, currently only supports OpenAI's ChatGPT API. 
Create an account at https://beta.openai.com/signup, then generate a secret key at https://beta.openai.com/account/api-keys and fill it inside the key field in the plugin configuration page.

# Changelog

## v1.1

Added tracking for requests and differential settings to enable only in exercises, only in learning paths, or both.

To update from v1.0, execute the following queries manually.
```sql
CREATE TABLE plugin_ai_helper_requests (
id int(11) NOT NULL AUTO_INCREMENT,
user_id int(11) NOT NULL,
tool_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
requested_at datetime DEFAULT NULL,
request_text varchar(255) COLLATE utf8_unicode_ci NOT NULL,
prompt_tokens int(11) NOT NULL,
completion_tokens int(11) NOT NULL,
total_tokens int(11) NOT NULL,
PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
```
If you got this update through Git, you will also need to run `composer install` to update the autoload mechanism.
