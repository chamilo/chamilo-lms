IMS/LTI plugin
===

Version 1.1 (beta)

This plugin is meant to be later integrated into Chamilo (in a major version
release).

IMS/LTI defines the possibility to integrate tools or content into Chamilo.
This plugin allows the integration of a new tool into courses, without (for now)
obtaining any data back from those tools.
It will gradually be developed to support IMS/LTI content items.

As platform admin you can register external tools available for all courses.
You need set the tools settings in the IMS/LTI administration page.
Then the registered tools should be add in each course individually.

As teacher you can register external tools available only for the current
course. You need follow the link in the IMS/LTI block located in the Course
Settings tool. Then select a previously tool registered or register a new
external tool.

# Changelog

**v1.1**

* Support for Deep-Linking added.

# Installation

1. Install the plugin from Plugin page
2. Enable the plugin from Plugin Settings page
3. Assign to the Administrator region

# Upgrading

**To v1.1**

Run this changes on database:
```sql
ALTER TABLE plugin_ims_lti_tool
    ADD active_deep_linking TINYINT(1) DEFAULT '0' NOT NULL,
    CHANGE id id INT AUTO_INCREMENT NOT NULL,
    CHANGE launch_url launch_url VARCHAR(255) NOT NULL;
```
