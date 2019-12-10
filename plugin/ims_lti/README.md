IMS/LTI plugin
===

Version 1.5.1

> This plugin is meant to be later integrated into Chamilo (in a major version
release).

IMS/LTI defines the possibility to integrate tools or content into Chamilo.
This plugin allows the integration of a new tool into courses, obtaining 
data back from those tools and recording them as gradebook "external" activities.

This plugin allow certified support for LTI 1.0, 1.1, 1.1.1, Deep Linking 1.x, Outcome Services 1.x.
You can get information about the LTI Certification on [this page][certification link].

As a platform admin, you can register external tools available for all courses.
You need set the tools settings in the IMS/LTI administration page.
Then the registered tools should be added in each course individually.

As a teacher, you can register external tools available only for the current
course. You need follow the link in the IMS/LTI block located in the Course
Settings tool. Then select a previously tool registered or register a new
external tool.

# Changelog

## v1.5
* Plugin has passed the tests from the LTI Certification suite.
* Add support for substitution of variable.
  See `ImsLti::getSubstitutableParams()`.
* Outcome services has a unique URL and sourced ID.

## v1.4
* Allow create external tools when there is no key/secret available for launch

## v1.3
* Privacy settings added. Allow to indicate id the launcher's data
  should be sent in request.

## v1.2
* Register course in which the tool was added.
* Register parent tool from which the new tool comes from.

## v1.1
* Support for Deep-Linking added.
* Support for outcomes services. And register score on course gradebook.

# Installation

1. Install the plugin from the Plugins page
2. Enable the plugin from the IMS/LTI Plugin Settings page
3. Assign to the Administrator region (in the regions management page)

# Upgrading

Run this changes on database:

## To v1.5.1
```sql
ALTER TABLE plugin_ims_lti_tool
    DROP FOREIGN KEY FK_C5E47F7C727ACA70,
    ADD FOREIGN KEY (parent_id) REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE ON UPDATE RESTRICT;
```

## To v1.4
```sql
ALTER TABLE plugin_ims_lti_tool
    CHANGE consumer_key consumer_key VARCHAR(255) DEFAULT NULL,
    CHANGE shared_secret shared_secret VARCHAR(255) DEFAULT NULL;
```

## To v1.3
```sql
ALTER TABLE plugin_ims_lti_tool ADD privacy LONGTEXT DEFAULT NULL;
```

## To v1.2
```sql
ALTER TABLE plugin_ims_lti_tool ADD c_id INT DEFAULT NULL;
ALTER TABLE plugin_ims_lti_tool ADD CONSTRAINT FK_C5E47F7C91D79BD3
    FOREIGN KEY (c_id) REFERENCES course (id);
CREATE INDEX IDX_C5E47F7C91D79BD3 ON plugin_ims_lti_tool (c_id);

ALTER TABLE plugin_ims_lti_tool ADD parent_id INT DEFAULT NULL, DROP is_global;
ALTER TABLE plugin_ims_lti_tool ADD CONSTRAINT FK_C5E47F7C727ACA70
    FOREIGN KEY (parent_id) REFERENCES plugin_ims_lti_tool (id);
CREATE INDEX IDX_C5E47F7C727ACA70 ON plugin_ims_lti_tool (parent_id);
```

## To v1.1
```sql
ALTER TABLE plugin_ims_lti_tool
    ADD active_deep_linking TINYINT(1) DEFAULT '0' NOT NULL,
    CHANGE id id INT AUTO_INCREMENT NOT NULL,
    CHANGE launch_url launch_url VARCHAR(255) NOT NULL;
    
ALTER TABLE plugin_ims_lti_tool ADD gradebook_eval_id INT DEFAULT NULL;
ALTER TABLE plugin_ims_lti_tool ADD CONSTRAINT FK_C5E47F7C82F80D8B
    FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id)
    ON DELETE SET NULL;
CREATE INDEX IDX_C5E47F7C82F80D8B ON plugin_ims_lti_tool (gradebook_eval_id);
```

[certification link]: https://site.imsglobal.org/certifications/asociacion-chamilo/156616/chamilo+lms