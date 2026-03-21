Experience API (xAPI)
=====================

Overview
--------
The XApi plugin for Chamilo 2 provides two main capabilities:

1. launch TinCan and cmi5 packages as course activities,
2. generate xAPI statements from native Chamilo events such as learning paths, quizzes and portfolio actions.

It also exposes a local lightweight xAPI endpoint that can be used by imported activities and by internal plugin flows.

Current status
--------------
- TinCan package import and launch are supported.
- cmi5 package import and launch are available in beta.
- The plugin includes a local xAPI endpoint at `plugin/XApi/lrs`.
- Native Chamilo event hooks can generate xAPI statements depending on the global plugin settings.

Important database note
-----------------------
Do not create MySQL tables manually.

In Chamilo 2, the required database structure is managed by the platform installation and migrations.

Main plugin pages
-----------------
Course tool pages:
- `plugin/XApi/start.php`
  Lists imported xAPI activities in the current course.
- `plugin/XApi/tool_import.php`
  Imports TinCan or cmi5 packages.
- `plugin/XApi/tool_edit.php`
  Edits an imported activity.
- `plugin/XApi/tool_delete.php`
  Deletes an imported activity.
- `plugin/XApi/tincan/view.php`
  TinCan activity launch page.
- `plugin/XApi/cmi5/view.php`
  cmi5 activity launch page.
- `plugin/XApi/tincan/stats.php`
  TinCan reporting page.

Technical pages:
- `plugin/XApi/lrs`
  Main local xAPI endpoint.
- `plugin/XApi/cmi5/token.php`
  Token endpoint used during cmi5 launches.
- `plugin/XApi/admin.php`
  Local LRS credential management page. This page may need to be opened manually by URL depending on the current platform navigation.

Global configuration
--------------------
The global plugin configuration defines the default xAPI and LRS behavior for the whole plugin.

Typical stored values:
- `uuid_namespace`
- `lrs_url`
- `lrs_auth_username`
- `lrs_auth_password`
- `cron_lrs_url`
- `cron_lrs_auth_username`
- `cron_lrs_auth_password`
- `lrs_lp_item_viewed_active`
- `lrs_lp_end_active`
- `lrs_quiz_active`
- `lrs_quiz_question_active`
- `lrs_portfolio_active`
- `defaultVisibilityInCourseHomepage`

Parameter summary
-----------------
1. `uuid_namespace`
   Default UUID namespace used for deterministic xAPI-related identifiers.
   Recommended: keep the generated value provided by the plugin.

2. `lrs_url`
   Default LRS endpoint used by the plugin.
   Examples:
    - Local LRS: `https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs`
    - External LRS: `https://your-lrs.example.com/xapi`

3. `lrs_auth_username`
   Default username for the configured LRS.

4. `lrs_auth_password`
   Default password for the configured LRS.

5. `cron_lrs_url`
   Optional LRS endpoint used only by the cron sender.

6. `cron_lrs_auth_username`
   Optional cron-specific username.

7. `cron_lrs_auth_password`
   Optional cron-specific password.

8. `lrs_lp_item_viewed_active`
   Enable xAPI generation when a learner views a learning path item.

9. `lrs_lp_end_active`
   Enable xAPI generation when a learner completes a learning path.

10. `lrs_quiz_active`
    Enable xAPI generation when a learner finishes a quiz.

11. `lrs_quiz_question_active`
    Enable xAPI generation when a learner answers a quiz question.

12. `lrs_portfolio_active`
    Enable xAPI generation for portfolio actions.

13. `defaultVisibilityInCourseHomepage`
    Controls whether the XApi course tool is visible by default in newly created courses.

Recommended configuration scenarios
-----------------------------------
### A. Local runtime for imported TinCan/cmi5 packages
Use:
- `lrs_url = https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs`
- `lrs_auth_username = valid local xAPI credential`
- `lrs_auth_password = matching password`

This is the recommended setup for local package runtime testing.

### B. Sending native Chamilo statements to an external LRS through cron
Use:
- `cron_lrs_url = https://your-external-lrs.example.com/xapi`
- `cron_lrs_auth_username = external username`
- `cron_lrs_auth_password = external password`

Use this when hook-generated statements must be delivered to a dedicated external LRS.

Imported activity configuration
-------------------------------
Each imported activity can override some global values in `tool_edit.php`.

Available fields:
- `title`
- `description`
- `allow_multiple_attempts`
- `lrs_url`
- `lrs_auth_username`
- `lrs_auth_password`

Usage:
- leave these fields empty to use the global plugin configuration,
- fill them only when a specific activity must use a different LRS.

Import and launch workflow
--------------------------
### TinCan
1. Open the XApi tool inside a course.
2. Click Import.
3. Upload a TinCan package.
4. The plugin extracts the package and creates the course activity.
5. Launch the activity from the course interface.

### cmi5
1. Open the XApi tool inside a course.
2. Click Import.
3. Upload a cmi5 package.
4. Launch the activity through the cmi5 flow.

Notes:
- TinCan is currently the most stable package-based workflow.
- cmi5 is still considered beta.

Local LRS
---------
The plugin includes a local xAPI endpoint at:

`https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs`

This endpoint is used for:
- TinCan runtime requests,
- cmi5 runtime requests,
- local testing,
- internal plugin communication.

Required headers for statement/state requests:
- `Authorization: Basic ...`
- `X-Experience-API-Version: 1.0.3`
- `Content-Type: application/json`

Common supported operations:
- `about`
- `statements`
- `activities/state`

Internal queue and cron sender
------------------------------
When native Chamilo hooks are enabled, generated statements are stored in the internal queue table:

- `xapi_shared_statement`

Cron sender:
- `plugin/XApi/cron/send_statements.php`

The cron sender processes pending rows and marks them as sent after successful delivery.

Validated behavior in Chamilo 2
-------------------------------
The following behaviors have been manually validated in a Chamilo 2 environment:

### Local LRS
- `GET /plugin/XApi/lrs/about`
- `GET /plugin/XApi/lrs/statements`
- `POST /plugin/XApi/lrs/statements`
- `GET /plugin/XApi/lrs/activities/state`
- `PUT /plugin/XApi/lrs/activities/state`

### Native hook-based statements
- Quiz question answered
- Quiz completed
- Learning path item viewed
- Learning path completed

### Internal queue and delivery
- Hook-generated statements are stored in `xapi_shared_statement`
- The cron sender processes pending statements and marks them as sent

### Course tool visibility
- `defaultVisibilityInCourseHomepage` affects whether the XApi course tool is visible by default in newly created courses

Portfolio-related events are part of the plugin design but should still be validated separately in the target environment.

Practical testing workflow
--------------------------
### Native Chamilo hooks
1. Configure:
    - `lrs_url`
    - `lrs_auth_username`
    - `lrs_auth_password`

2. Enable only one hook family at a time:
    - learning path
    - quiz
    - portfolio

3. Trigger a real learner action.

4. Verify that a row was added to:
    - `xapi_shared_statement`

5. Run:
    - `plugin/XApi/cron/send_statements.php`

6. Verify that processed rows were marked as sent.

Examples:
- Quiz only:
    - `lrs_quiz_active = true`
    - `lrs_quiz_question_active = true`
- Learning path only:
    - `lrs_lp_item_viewed_active = true`
    - `lrs_lp_end_active = true`
- Portfolio only:
    - `lrs_portfolio_active = true`

### Imported activities
1. Configure `lrs_url` to the local endpoint.
2. Use valid local LRS credentials.
3. Import a TinCan package first.
4. Then test cmi5 packages.

Notes
-----
- `cron_lrs_url`, `cron_lrs_auth_username` and `cron_lrs_auth_password` are optional.
  They are only needed when the cron sender must use a different LRS than the main plugin configuration.

- `defaultVisibilityInCourseHomepage` is mainly relevant for newly created courses.
  Existing courses may already have their own stored tool visibility.

- `plugin/XApi/admin.php` is mainly useful to manage local LRS credentials used by the local endpoint.
  It is not required for normal quiz or learning path testing if the configured local credentials already work.

Summary
-------
Use this plugin when you want one or both of these capabilities:

- launch xAPI-compatible packaged content in Chamilo,
- generate xAPI statements from native Chamilo activity.

For current Chamilo 2 usage, the most important configuration fields are:
- `lrs_url`
- `lrs_auth_username`
- `lrs_auth_password`
- `cron_lrs_url`
- `cron_lrs_auth_username`
- `cron_lrs_auth_password`
- hook activation flags

Do not add SQL manually. In Chamilo 2, the required database structure is managed by the platform installation and migrations.
