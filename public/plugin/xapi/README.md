# Experience API (xAPI)

Allows you to connect to an external Learning Record Store and use activities with the xAPI standard.

> You can import and use TinCan packages.
> Import CMI5 packages is to be considered a Beta state and still in development. 

**Configuration**

Set LRS endpoint, username and password to integrate an external LRS in Chamilo LMS.

The fields "Learning path item viewed", "Learning path ended", "Quiz question answered" and "Quiz ended" allow enabling
hooks when the user views an item in learning path, completes a learning path, answers a quiz question and ends the exam.

The statements generated with these hooks are logged in Chamilo database, waiting to be sent to the LRS by a cron job.
The cron job to configure on your server is located in `CHAMILO_PATH/plugin/xapi/cron/send_statements.php`.

**Use the Statement API from Chamilo LMS**

You can use xAPI's "Statement API" to save some statements from another service.
You need to create credentials (username/password) to do this. First you need to enable the "menu_administrator" region
in the plugin configuration. You will then be able to create the credentials with the new page "Experience API (xAPI)"
inside de Plugins block in the Administration panel.
The endpoint for the statements API is "https://CHAMILO_DOMAIN/plugin/xapi/lrs.php/";

```mysql
CREATE TABLE xapi_attachment (identifier INT AUTO_INCREMENT NOT NULL, statement_id VARCHAR(255) DEFAULT NULL, usageType VARCHAR(255) NOT NULL, contentType VARCHAR(255) NOT NULL, length INT NOT NULL, sha2 VARCHAR(255) NOT NULL, display LONGTEXT NOT NULL COMMENT '(DC2Type:json)', hasDescription TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', fileUrl VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, INDEX IDX_7148C9A1849CB65B (statement_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_object (identifier INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, actor_id INT DEFAULT NULL, verb_id INT DEFAULT NULL, object_id INT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, activityId VARCHAR(255) DEFAULT NULL, hasActivityDefinition TINYINT(1) DEFAULT NULL, hasActivityName TINYINT(1) DEFAULT NULL, activityName LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', hasActivityDescription TINYINT(1) DEFAULT NULL, activityDescription LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', activityType VARCHAR(255) DEFAULT NULL, activityMoreInfo VARCHAR(255) DEFAULT NULL, mbox VARCHAR(255) DEFAULT NULL, mboxSha1Sum VARCHAR(255) DEFAULT NULL, openId VARCHAR(255) DEFAULT NULL, accountName VARCHAR(255) DEFAULT NULL, accountHomePage VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, referenced_statement_id VARCHAR(255) DEFAULT NULL, activityExtensions_id INT DEFAULT NULL, parentContext_id INT DEFAULT NULL, groupingContext_id INT DEFAULT NULL, categoryContext_id INT DEFAULT NULL, otherContext_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E2B68640303C7F1D (activityExtensions_id), INDEX IDX_E2B68640FE54D947 (group_id), UNIQUE INDEX UNIQ_E2B6864010DAF24A (actor_id), UNIQUE INDEX UNIQ_E2B68640C1D03483 (verb_id), UNIQUE INDEX UNIQ_E2B68640232D562B (object_id), INDEX IDX_E2B68640988A4CEC (parentContext_id), INDEX IDX_E2B686404F542860 (groupingContext_id), INDEX IDX_E2B68640AEA1B132 (categoryContext_id), INDEX IDX_E2B68640B73EEAB7 (otherContext_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_result (identifier INT AUTO_INCREMENT NOT NULL, extensions_id INT DEFAULT NULL, hasScore TINYINT(1) NOT NULL, scaled DOUBLE PRECISION DEFAULT NULL, raw DOUBLE PRECISION DEFAULT NULL, min DOUBLE PRECISION DEFAULT NULL, max DOUBLE PRECISION DEFAULT NULL, success TINYINT(1) DEFAULT NULL, completion TINYINT(1) DEFAULT NULL, response VARCHAR(255) DEFAULT NULL, duration VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5971ECBFD0A19400 (extensions_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_verb (identifier INT AUTO_INCREMENT NOT NULL, id VARCHAR(255) NOT NULL, display LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_extensions (identifier INT AUTO_INCREMENT NOT NULL, extensions LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_context (identifier INT AUTO_INCREMENT NOT NULL, instructor_id INT DEFAULT NULL, team_id INT DEFAULT NULL, extensions_id INT DEFAULT NULL, registration VARCHAR(255) DEFAULT NULL, hasContextActivities TINYINT(1) DEFAULT NULL, revision VARCHAR(255) DEFAULT NULL, platform VARCHAR(255) DEFAULT NULL, language VARCHAR(255) DEFAULT NULL, statement VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3D7771908C4FC193 (instructor_id), UNIQUE INDEX UNIQ_3D777190296CD8AE (team_id), UNIQUE INDEX UNIQ_3D777190D0A19400 (extensions_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_actor (identifier INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) DEFAULT NULL, mbox VARCHAR(255) DEFAULT NULL, mboxSha1Sum VARCHAR(255) DEFAULT NULL, openId VARCHAR(255) DEFAULT NULL, accountName VARCHAR(255) DEFAULT NULL, accountHomePage VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, members VARCHAR(255) NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_statement (id VARCHAR(255) NOT NULL, actor_id INT DEFAULT NULL, verb_id INT DEFAULT NULL, object_id INT DEFAULT NULL, result_id INT DEFAULT NULL, authority_id INT DEFAULT NULL, context_id INT DEFAULT NULL, created BIGINT DEFAULT NULL, `stored` BIGINT DEFAULT NULL, hasAttachments TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_BAF6663B10DAF24A (actor_id), UNIQUE INDEX UNIQ_BAF6663BC1D03483 (verb_id), UNIQUE INDEX UNIQ_BAF6663B232D562B (object_id), UNIQUE INDEX UNIQ_BAF6663B7A7B643 (result_id), UNIQUE INDEX UNIQ_BAF6663B81EC865B (authority_id), UNIQUE INDEX UNIQ_BAF6663B6B00C1CF (context_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
ALTER TABLE xapi_attachment ADD CONSTRAINT FK_7148C9A1849CB65B FOREIGN KEY (statement_id) REFERENCES xapi_statement (id);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640303C7F1D FOREIGN KEY (activityExtensions_id) REFERENCES xapi_extensions (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640FE54D947 FOREIGN KEY (group_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B6864010DAF24A FOREIGN KEY (actor_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640C1D03483 FOREIGN KEY (verb_id) REFERENCES xapi_verb (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640232D562B FOREIGN KEY (object_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640988A4CEC FOREIGN KEY (parentContext_id) REFERENCES xapi_context (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B686404F542860 FOREIGN KEY (groupingContext_id) REFERENCES xapi_context (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640AEA1B132 FOREIGN KEY (categoryContext_id) REFERENCES xapi_context (identifier);
ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640B73EEAB7 FOREIGN KEY (otherContext_id) REFERENCES xapi_context (identifier);
ALTER TABLE xapi_result ADD CONSTRAINT FK_5971ECBFD0A19400 FOREIGN KEY (extensions_id) REFERENCES xapi_extensions (identifier);
ALTER TABLE xapi_context ADD CONSTRAINT FK_3D7771908C4FC193 FOREIGN KEY (instructor_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_context ADD CONSTRAINT FK_3D777190296CD8AE FOREIGN KEY (team_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_context ADD CONSTRAINT FK_3D777190D0A19400 FOREIGN KEY (extensions_id) REFERENCES xapi_extensions (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B10DAF24A FOREIGN KEY (actor_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663BC1D03483 FOREIGN KEY (verb_id) REFERENCES xapi_verb (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B232D562B FOREIGN KEY (object_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B7A7B643 FOREIGN KEY (result_id) REFERENCES xapi_result (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B81EC865B FOREIGN KEY (authority_id) REFERENCES xapi_object (identifier);
ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B6B00C1CF FOREIGN KEY (context_id) REFERENCES xapi_context (identifier);

CREATE TABLE xapi_shared_statement (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) DEFAULT NULL, statement LONGTEXT NOT NULL COMMENT '(DC2Type:array)', sent TINYINT(1) DEFAULT '0' NOT NULL, INDEX idx_uuid (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

CREATE TABLE xapi_lrs_auth (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

CREATE TABLE xapi_tool_launch (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, launch_url VARCHAR(255) NOT NULL, activity_id VARCHAR(255) DEFAULT NULL, activity_type VARCHAR(255) DEFAULT NULL, allow_multiple_attempts TINYINT(1) DEFAULT '1' NOT NULL, lrs_url VARCHAR(255) DEFAULT NULL, lrs_auth_username VARCHAR(255) DEFAULT NULL, lrs_auth_password VARCHAR(255) DEFAULT NULL, INDEX IDX_E18CB58391D79BD3 (c_id), INDEX IDX_E18CB583613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
ALTER TABLE xapi_tool_launch ADD CONSTRAINT FK_E18CB58391D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);
ALTER TABLE xapi_tool_launch ADD CONSTRAINT FK_E18CB583613FECDF FOREIGN KEY (session_id) REFERENCES session (id);

CREATE TABLE xapi_cmi5_item (id INT AUTO_INCREMENT NOT NULL, tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, identifier VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, title LONGTEXT NOT NULL COMMENT '(DC2Type:json)', description LONGTEXT NOT NULL COMMENT '(DC2Type:json)', url VARCHAR(255) DEFAULT NULL, activity_type VARCHAR(255) DEFAULT NULL, launch_method VARCHAR(255) DEFAULT NULL, move_on VARCHAR(255) DEFAULT NULL, mastery_score DOUBLE PRECISION DEFAULT NULL, launch_parameters VARCHAR(255) DEFAULT NULL, entitlement_key VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, tool_id INT DEFAULT NULL, INDEX IDX_7CA116D88F7B22CC (tool_id), INDEX IDX_7CA116D8A977936C (tree_root), INDEX IDX_7CA116D8727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
ALTER TABLE xapi_cmi5_item ADD CONSTRAINT FK_7CA116D8A977936C FOREIGN KEY (tree_root) REFERENCES xapi_cmi5_item (id) ON DELETE CASCADE;
ALTER TABLE xapi_cmi5_item ADD CONSTRAINT FK_7CA116D8727ACA70 FOREIGN KEY (parent_id) REFERENCES xapi_cmi5_item (id) ON DELETE CASCADE;

CREATE TABLE xapi_activity_state (id INT AUTO_INCREMENT NOT NULL, state_id VARCHAR(255) NOT NULL, activity_id VARCHAR(255) NOT NULL, agent LONGTEXT NOT NULL COMMENT '(DC2Type:json)', document_data LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
CREATE TABLE xapi_activity_profile (id INT AUTO_INCREMENT NOT NULL, profile_id VARCHAR(255) NOT NULL, activity_id VARCHAR(255) NOT NULL, document_data LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
```

**From 0.2 (beta) [2021-10-15]**
- With the LRS an internal log is registered based on the actor mbox's email or the actor account's name coming from the statement
To update, execute this queries:

```sql
CREATE TABLE xapi_internal_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, statement_id VARCHAR(255) NOT NULL, verb VARCHAR(255) NOT NULL, object_id VARCHAR(255) NOT NULL, activity_name VARCHAR(255) DEFAULT NULL, activity_description VARCHAR(255) DEFAULT NULL, score_scaled DOUBLE PRECISION DEFAULT NULL, score_raw DOUBLE PRECISION DEFAULT NULL, score_min DOUBLE PRECISION DEFAULT NULL, score_max DOUBLE PRECISION DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_C1C667ACA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
ALTER TABLE xapi_internal_log ADD CONSTRAINT FK_C1C667ACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
```

**From 0.3 (beta) [2021-11-11]**

- Fix: Add foreign keys with course/session in tool_launch table and foreign key with user in internal_log table.
```sql
ALTER TABLE xapi_internal_log ADD CONSTRAINT FK_C1C667ACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
ALTER TABLE xapi_tool_launch ADD CONSTRAINT FK_E18CB58391D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE;
ALTER TABLE xapi_tool_launch ADD CONSTRAINT FK_E18CB583613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE;
```
