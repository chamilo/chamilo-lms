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
