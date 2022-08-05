IMS/LTI plugin
===

Version 1.9.0

> This plugin is meant to be later integrated into Chamilo (in a major version
> release).

This plugin allows certified support for LTI 1.0, 1.1, 1.1.1, Deep Linking 1.x, Outcome Services 1.x.
You can get information about the LTI Certification on [this page][certification link].
The LTI 1.3 is being developed.

IMS/LTI defines the possibility to integrate tools or content into Chamilo.
This plugin allows the integration of a new tool into courses, obtaining
data back from those tools and recording them as gradebook "external" activities.

As a platform admin, you can register external tools available for all courses.
You need set the tools settings in the IMS/LTI administration page.
Then the registered tools should be added in each course individually.

As a teacher, you can register external tools available only for the current
course. You need follow the link in the IMS/LTI block located in the Course
Settings tool. Then select a tool registered previously or register a new
external tool.

# Installation

* Prepare your web server to allow to send cookies in all contexts, set the `SameSite` attribute to `None`
    * i.e. Apache configuration

      ```apacheconf
      Header edit Set-Cookie ^(.*)$ $1;HttpOnly;Secure;SameSite=None
      ```

1. Install the plugin from the Plugins page
2. Enable the plugin from the IMS/LTI Plugin Settings page
3. Assign to the Administrator region (in the regions management page)

# Changelog

## v1.9

> Requires DB changes to upgrade, see [v1.9](#to-v190).

* Add option to add LTI tool to sessions

## v1.8

> Requires DB changes to upgrade, see [v1.8](#to-v180).

* Add option to add replacements for launch params

## v1.7

> Requires DB changes to upgrade, see [v1.8](#to-v170).

* Fix auth params
* Add option to show LTI tool in iframe or new window.

## v1.6

> Requires DB changes to upgrade, see [v1.8](#to-v160).

* Add support to LTI 1.3 and Advantage Services

## v1.5

> Requires DB changes to upgrade, see [v1.8](#to-v151).

* Plugin has passed the tests from the LTI Certification suite.
* Add support for substitution of variable.
  See `ImsLti::getSubstitutableParams()`.
* Outcome services has a unique URL and sourced ID.

## v1.4

> Requires DB changes to upgrade, see [v1.8](#to-v140).

* Allow create external tools when there is no key/secret available for launch

## v1.3

> Requires DB changes to upgrade, see [v1.8](#to-v130).

* Privacy settings added. Allow to indicate id the launcher's data
  should be sent in request.

## v1.2

> Requires DB changes to upgrade, see [v1.8](#to-v120).

* Register course in which the tool was added.
* Register parent tool from which the new tool comes from.

## v1.1

> Requires DB changes to upgrade, see [v1.8](#to-v110).

* Support for Deep-Linking added.
* Support for outcomes services. And register score on course gradebook.

# Upgrading

Run this changes on database:

## To v1.9.0

```sql
ALTER TABLE plugin_ims_lti_tool
    ADD session_id INT DEFAULT NULL;
ALTER TABLE plugin_ims_lti_tool
    ADD CONSTRAINT FK_C5E47F7C613FECDF FOREIGN KEY (session_id) REFERENCES session (id);
CREATE INDEX IDX_C5E47F7C613FECDF ON plugin_ims_lti_tool (session_id);
```

## To v1.8.0

```sql
ALTER TABLE plugin_ims_lti_tool
    ADD replacement_params LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
```

## To v1.7.0

```sql
ALTER TABLE plugin_ims_lti_tool
    ADD launch_presentation LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
```

## To v1.6.0

```sql
CREATE TABLE plugin_ims_lti_platform
(
    id          INT AUTO_INCREMENT NOT NULL,
    kid         VARCHAR(255)       NOT NULL,
    public_key  LONGTEXT           NOT NULL,
    private_key LONGTEXT           NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci
  ENGINE = InnoDB;
CREATE TABLE plugin_ims_lti_token
(
    id         INT AUTO_INCREMENT NOT NULL,
    tool_id    INT DEFAULT NULL,
    scope      LONGTEXT           NOT NULL COMMENT '(DC2Type:json)',
    hash       VARCHAR(255)       NOT NULL,
    created_at INT                NOT NULL,
    expires_at INT                NOT NULL,
    INDEX IDX_F7B5692F8F7B22CC (tool_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci
  ENGINE = InnoDB;
ALTER TABLE plugin_ims_lti_token
    ADD CONSTRAINT FK_F7B5692F8F7B22CC FOREIGN KEY (tool_id) REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE;
ALTER TABLE plugin_ims_lti_tool
    ADD client_id          VARCHAR(255) DEFAULT NULL,
    ADD public_key         LONGTEXT     DEFAULT NULL,
    ADD login_url          VARCHAR(255) DEFAULT NULL,
    ADD redirect_url       VARCHAR(255) DEFAULT NULL,
    ADD advantage_services LONGTEXT     DEFAULT NULL COMMENT '(DC2Type:json)',
    ADD version            VARCHAR(255) DEFAULT 'lti1p1' NOT NULL;
CREATE TABLE plugin_ims_lti_lineitem
(
    id          INT AUTO_INCREMENT NOT NULL,
    tool_id     INT                NOT NULL,
    evaluation  INT                NOT NULL,
    resource_id VARCHAR(255) DEFAULT NULL,
    tag         VARCHAR(255) DEFAULT NULL,
    start_date  DATETIME     DEFAULT NULL,
    end_date    DATETIME     DEFAULT NULL,
    INDEX IDX_BA81BBF08F7B22CC (tool_id),
    UNIQUE INDEX UNIQ_BA81BBF01323A575 (evaluation),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci
  ENGINE = InnoDB;
ALTER TABLE plugin_ims_lti_lineitem
    ADD CONSTRAINT FK_BA81BBF08F7B22CC FOREIGN KEY (tool_id) REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE;
ALTER TABLE plugin_ims_lti_lineitem
    ADD CONSTRAINT FK_BA81BBF01323A575 FOREIGN KEY (evaluation) REFERENCES gradebook_evaluation (id) ON DELETE CASCADE;
```

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
ALTER TABLE plugin_ims_lti_tool
    ADD privacy LONGTEXT DEFAULT NULL;
```

## To v1.2

```sql
ALTER TABLE plugin_ims_lti_tool
    ADD c_id INT DEFAULT NULL;
ALTER TABLE plugin_ims_lti_tool
    ADD CONSTRAINT FK_C5E47F7C91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);
CREATE INDEX IDX_C5E47F7C91D79BD3 ON plugin_ims_lti_tool (c_id);

ALTER TABLE plugin_ims_lti_tool
    ADD parent_id INT DEFAULT NULL,
    DROP is_global;
ALTER TABLE plugin_ims_lti_tool
    ADD CONSTRAINT FK_C5E47F7C727ACA70 FOREIGN KEY (parent_id) REFERENCES plugin_ims_lti_tool (id);
CREATE INDEX IDX_C5E47F7C727ACA70 ON plugin_ims_lti_tool (parent_id);
```

## To v1.1

```sql
ALTER TABLE plugin_ims_lti_tool
    ADD active_deep_linking TINYINT(1) DEFAULT '0' NOT NULL,
    CHANGE id id INT AUTO_INCREMENT NOT NULL,
    CHANGE launch_url launch_url VARCHAR(255) NOT NULL;

ALTER TABLE plugin_ims_lti_tool
    ADD gradebook_eval_id INT DEFAULT NULL;
ALTER TABLE plugin_ims_lti_tool
    ADD CONSTRAINT FK_C5E47F7C82F80D8B FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;
CREATE INDEX IDX_C5E47F7C82F80D8B ON plugin_ims_lti_tool (gradebook_eval_id);
```

[certification link]: https://site.imsglobal.org/certifications/asociacion-chamilo/156616/chamilo+lms
