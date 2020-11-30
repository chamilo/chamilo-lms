# Experience API (xAPI)

Allow to incorporate an external Learning Record Store and use activities with the xAPI.

> You can import and use TinCan packages.
> Import Cmi5 packages is beta state and development. 

**Configuration**

Set LRS endpoint, username and password to itegrate an external LRS in Chamilo LMS.

The fields "Learning path item viewed", "Learning path ended", "Quiz question answered" and "Quiz ended" allow enabling
hooks when the user views an item in learning path, completes a learning path, answers a quiz question and ends the exam.

The statements generated with these hooks are logged in Chamilo database, waiting to be sent to the LRS by a cron job.
The cron job to configure in your server is located in `/CHAMILO_PATH/plugin/xapi/cron/send_statements.php`.

**Use the Statement API from Chamilo LMS**

You can use the xAPI statement API to save some statements from another service.
You need create credentials (username/password) to do this. First you need set the "menu_administrator" plugin region
to xAPI plugin. Then you can create the credentials with the new page "Experience API (xAPI)"
inside de Plugins block in the Administration panel.
