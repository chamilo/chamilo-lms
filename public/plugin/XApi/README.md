Experience API (xAPI)
=====================

Overview
--------
The XApi plugin for Chamilo 2 lets you:

1. connect Chamilo to a Learning Record Store (LRS),
2. import and launch TinCan and cmi5 packages as course activities,
3. expose a local xAPI endpoint for package runtime communication,
4. generate xAPI statements from native Chamilo events such as learning paths, quizzes and portfolio actions.

Current status
--------------
- TinCan package import and launch are supported.
- cmi5 package import and launch are available in beta.
- The plugin includes a local lightweight LRS endpoint used by imported activities and by internal plugin flows.
- Native Chamilo event hooks can generate xAPI statements depending on the global plugin settings.

Important note about the database
---------------------------------
Do not create MySQL tables manually.
In Chamilo 2, the required tables are already created by the platform installation and migrations.

How the plugin works
--------------------
The plugin currently has two main responsibilities:

1. Package-based activities
    - Teachers can import TinCan or cmi5 packages inside a course.
    - Imported activities appear in the course XApi tool list.
    - TinCan activities can be launched from the course interface.
    - cmi5 activities can be launched through the cmi5 flow, including token retrieval for the AU.
    - Local package files are extracted and served by the plugin.

2. Native Chamilo xAPI statement generation
    - The plugin can listen to Chamilo events such as:
        - learning path item viewed,
        - learning path completed,
        - quiz question answered,
        - quiz completed,
        - portfolio actions.
    - When these hooks are enabled, Chamilo generates xAPI statements and stores them in the internal shared statement log.
    - These stored statements can later be sent to an external LRS by the plugin cron process.

Main pages in the plugin
------------------------
Course tool pages:
- plugin/XApi/start.php
  Lists imported xAPI activities in the current course.
- plugin/XApi/tool_import.php
  Imports TinCan or cmi5 packages.
- plugin/XApi/tool_edit.php
  Edits an imported activity configuration.
- plugin/XApi/tool_delete.php
  Deletes an imported activity.
- plugin/XApi/tincan/view.php
  TinCan activity view and launch page.
- plugin/XApi/cmi5/view.php
  cmi5 activity view and launch page.
- plugin/XApi/tincan/stats.php
  TinCan reporting page.
- plugin/XApi/cmi5/stats.php
  cmi5 reporting page, if enabled in the current codebase.

Local xAPI endpoints:
- plugin/XApi/lrs
  Main local xAPI endpoint.
- plugin/XApi/cmi5/token.php
  Token endpoint used during cmi5 launches.

Global plugin configuration page
--------------------------------
The global plugin configuration is used to define the default LRS behavior for the whole plugin.
Typical stored values look like this:

- uuid_namespace
- lrs_url
- lrs_auth_username
- lrs_auth_password
- cron_lrs_url
- cron_lrs_auth_username
- cron_lrs_auth_password
- lrs_lp_item_viewed_active
- lrs_lp_end_active
- lrs_quiz_active
- lrs_quiz_question_active
- lrs_portfolio_active
- defaultVisibilityInCourseHomepage

Explanation of each parameter
-----------------------------
1. uuid_namespace
   Default UUID namespace used when generating deterministic identifiers for statements or related xAPI data.
   Recommended value: keep the generated UUID provided by the plugin.

2. lrs_url
   Default LRS endpoint used by the plugin for activity launches and statement submission.
   Common values:
    - Local plugin LRS:
      https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs
    - External LRS:
      https://your-lrs.example.com/xapi

3. lrs_auth_username
   Default username used to authenticate against the configured LRS.

4. lrs_auth_password
   Default password used to authenticate against the configured LRS.

5. cron_lrs_url
   Optional endpoint used by the cron task that sends internally logged shared statements to an external LRS.
   Use this when you want native Chamilo hooks to be sent to a different LRS than the one used for package launches.
   If empty, the plugin may fall back to the main LRS configuration depending on the code path.

6. cron_lrs_auth_username
   Username used by the cron sender for the external LRS.

7. cron_lrs_auth_password
   Password used by the cron sender for the external LRS.

8. lrs_lp_item_viewed_active
   Enables xAPI generation when a learner views a learning path item.
   Values:
    - true: enabled
    - false: disabled

9. lrs_lp_end_active
   Enables xAPI generation when a learner completes a learning path.
   Values:
    - true: enabled
    - false: disabled

10. lrs_quiz_active
    Enables xAPI generation when a learner finishes a quiz.
    Values:
    - true: enabled
    - false: disabled

11. lrs_quiz_question_active
    Enables xAPI generation when a learner answers a quiz question.
    Values:
    - true: enabled
    - false: disabled

12. lrs_portfolio_active
    Enables xAPI generation for portfolio events such as view, edit, comment, score, download or highlight.
    Values:
    - true: enabled
    - false: disabled

13. defaultVisibilityInCourseHomepage
    Controls whether the XApi course tool is visible by default in course homepages.
    Typical value:
    - visible

Recommended global configuration scenarios
------------------------------------------
A. Local runtime for imported TinCan/cmi5 packages
Use:
- lrs_url = https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs
- lrs_auth_username = a valid local xAPI credential
- lrs_auth_password = the matching password

This is the recommended setup when you want imported activities to communicate with the local XApi endpoint in Chamilo.

B. Sending Chamilo event statements to an external LRS through cron
Use:
- cron_lrs_url = https://your-external-lrs.example.com/xapi
- cron_lrs_auth_username = external username
- cron_lrs_auth_password = external password

Then enable the hook settings you want, such as learning path or quiz options.

Per-activity configuration page (tool_edit.php)
-----------------------------------------------
Each imported activity can override some defaults.
The edit page includes these fields:

1. title
   Human-readable activity title shown in the course tool.

2. description
   Optional text shown in the activity list and view page.

3. allow_multiple_attempts
   TinCan-specific option.
   When enabled, learners can launch multiple attempts for the activity.

4. lrs_url
   Optional per-activity LRS URL override.
   If filled, this activity will use this endpoint instead of the global lrs_url.

5. lrs_auth_username
   Optional per-activity username override.

6. lrs_auth_password
   Optional per-activity password override.

How to use these per-activity fields
------------------------------------
- Leave them empty if the activity should use the global plugin LRS settings.
- Fill them only when a specific TinCan or cmi5 activity must use a different LRS.
- For local testing, the per-activity LRS URL can also point to:
  https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs

Importing TinCan and cmi5 packages
----------------------------------
Teacher workflow:
1. Open the XApi tool inside a course.
2. Click Import.
3. Upload a TinCan or cmi5 package.
4. The plugin extracts the package and creates a course activity entry.
5. The activity appears in start.php with its type badge.

Notes:
- TinCan packages are the most stable path today.
- cmi5 is available in beta and still evolving.
- The package should contain the expected entry files in a format compatible with the importer.

Launching imported activities
-----------------------------
TinCan:
- The plugin builds a launch URL including endpoint, auth, actor, registration and activity_id.
- TinCan packages can be launched inside the Chamilo interface.

cmi5:
- The plugin builds the launch flow using the local cmi5 token endpoint.
- The AU receives the required launch parameters, including fetch and activityId.
- cmi5 support is still considered beta.

Local LRS behavior
------------------
The plugin includes a local xAPI endpoint at:
https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs

This endpoint is used for:
- TinCan runtime requests,
- cmi5 runtime requests,
- local testing,
- internal plugin communication.

For TinCan launches using the local LRS, the username and password must match a valid local xAPI credential known by the plugin.

Statement API usage
-------------------
The local xAPI endpoint can also be used as a Statement API endpoint by another service.
Typical endpoint:
https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs

Required headers:
- Authorization: Basic ...
- X-Experience-API-Version: 1.0.3
- Content-Type: application/json

Common operations handled by the local endpoint:
- statements
- activities/state
- about

Internal shared statement log and cron
--------------------------------------
When native Chamilo hooks are enabled, the plugin stores generated statements in the internal shared statement log.
This internal log is useful when:
- you want to keep a local queue of statements,
- you want to send them later to an external LRS,
- you want to decouple runtime events from LRS delivery.

Cron sender:
- plugin/XApi/cron/send_statements.php

Use the cron configuration fields when the sender must push data to a dedicated external LRS.

Testing recommendations
-----------------------
1. For imported activities
    - Configure lrs_url to the local endpoint:
      https://YOUR_CHAMILO_DOMAIN/plugin/XApi/lrs
    - Use valid local LRS credentials.
    - Import a TinCan package first.
    - Then test cmi5 packages.

2. For native event hooks
    - Enable only one family of hooks at a time:
        - learning path,
        - quiz,
        - portfolio.
    - Trigger a real learner action in Chamilo.
    - Verify that the plugin stores a shared statement.

Examples of hook-driven scenarios
---------------------------------
Learning path only:
- lrs_lp_item_viewed_active = true
- lrs_lp_end_active = true
- all other hook flags = false

Quiz only:
- lrs_quiz_active = true
- lrs_quiz_question_active = true
- all other hook flags = false

Portfolio only:
- lrs_portfolio_active = true
- all other hook flags = false

Summary
-------
Use this plugin when you want one or both of these capabilities:
- launch xAPI-compatible packaged content in Chamilo,
- generate xAPI statements from native Chamilo activity.

For current Chamilo 2 usage, the most important configuration fields are:
- lrs_url
- lrs_auth_username
- lrs_auth_password
- cron_lrs_url
- cron_lrs_auth_username
- cron_lrs_auth_password
- the hook activation flags

Do not add SQL manually. In Chamilo 2, the required database structure is managed by the platform installation and migrations.
