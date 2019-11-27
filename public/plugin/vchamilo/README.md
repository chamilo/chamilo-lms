Virtual Chamilo
===================

Authors : Valery Fremaux (valery.fremaux@gmail.com), Julio Montoya, Angel Quiroz, Yannick Warnier

Virtual Chamilo (or vChamilo) is a feature that allows you to easily run several chamilo instances 
sharing the same code base, with separate documents and databases, acting mostly like containers
sharing the same libraries.

With vChamilo, your first Chamilo portal acts as a container (or "controller"), and a seed
 (or image) for the Chamilo instances you will install afterwards. This first image should be 
 created automatically when configuring the plugin, but if it isn't, the plugin will tell you
 so and ask to generate one through the interface.
 
Once the plugin is fully setup, you will be able to create new Chamilo instances in a
 matter of minutes (or seconds on powerful servers).

Changelog
=========

*Version 1.7*

Added support for (optional) custom CSS folder
Database upgrade needed:

  ALTER TABLE vchamilo ADD COLUMN css_theme_folder varchar(255);


*Version 1.6*

Database upgrade needed:

  ALTER TABLE vchamilo ADD COLUMN archive_url varchar(255);
  ALTER TABLE vchamilo ADD COLUMN home_url varchar(255);
  ALTER TABLE vchamilo ADD COLUMN upload_url varchar(255);
  ALTER TABLE vchamilo ADD COLUMN course_url varchar(255);

*Version 1.5*

Improved usability and added validations. No DB update required.

*Version 1.4*

Database upgrade needed: 

  ALTER TABLE vchamilo ADD COLUMN password_encryption VARCHAR(255);

*Version 1.3*

Added vchamilo import


Version features
===================
This is still a beta version and it is not fully featured with back-office tools.
As such, you will be able to create, edit, copy, delete and even upgrade instances, but a certain
amount of manual work will still be required at the web server level to get your instances running.

How to setup
===================

To set this plugin up, you will need to:

1. Insert the virtualization hook into the Chamilo master configuration file and enable multi-urls:

```
<chamiloroot>/app/config/configuration.php
```

Insert the hook at the end of the file.

```
include_once $_configuration['root_sys'].'plugin/vchamilo/lib/Virtual.php';
Virtual::hookConfiguration($_configuration);
```
And add (or uncomment) the line to enable multi-url:
 
```
$_configuration['multiple_access_urls'] = true;
```
At this point, make sure there is no caching mechanism maintaining the previous configuration
 version before you continue. Enabling the multi-url option should have the immediate effect
  of adding a multi-url management link at the bottom of the "Platform" block in the 
  administration main page.

Take a moment to update the configuration of the default host in the multi-url configuration page
to the real hostname of your main (controller) portal.

2. Change the permissions on the <chamiloroot>/plugin/vchamilo/templates/ directory as it will
 be necessary for the plugin to create files and directories there
3. Create a common directory to be used for all Chamilo-related files. 
 We recommend using <chamiloroot>/var/ for that. Inside that directory, create the following 4
 directories: cache/, courses/, home/ and upload/ and give permissions to the web user to write
 into them (exactly the same way you did it for the app/ directory when installing Chamilo)
4. Enable and configure the plugin in the Chamilo administration's plugins list 
 (if in doubt, use the suggested values). Please note that the proxy configuration part is 
 totally optional and is not maintained by the Chamilo team at this point. 
5. Enable additional virtual hosts in your Apache config (unless you use subdirectories). All virtual hosts should point to the same DocumentRoot as the initial Chamilo installation.
6. For each virtual host or subdirectory, you will need to configure specific redirection rules (remember, this is still at beta-level):

```
RewriteEngine On
RewriteRule /app/upload/(.*)$ http://[vchamilo-instance-domain]/[selected-common-dir]/upload/[vchamilo-instance-dir]/$1 [QSA,L]
```
In the example above, you would need to replace everything that is currently within brackets, with
the specific details of each instance. For example:
```
RewriteRule /app/upload/(.*)$ http://beeznest.chamilo.net/var/upload/beeznest-chamilo-net/$1 [QSA,L]
```
Although your vChamilo instances *will* work basically without this rewrite rule, you will end 
up observing issues of files not appearing while uploading files on the instance.
 
Note that the domain of the instance, in the last part of the path, will be transformed 
from dot-separated domain (beeznest.chamilo.net) to dash-separated-domain (beeznest-chamilo-net).

7. Finally, go to the "Instances manager" and create new instances. Once an instance has been 
created, make sure you edit it and set the "Archive URL", "Home URL" and "Upload URL". These should
have the form of the "Web root" of the instance + the common directory + {archive|home|upload} + the 
instance domain with dots changed to hyphens.

For example, if you have used "var/" as a common directory and your instance URL (Web Root) is 
https://chamilo1.beeznest.com/, then your "Archive URL" would look like this:

  https://chamilo1.beeznest.com/var/archive/chamilo1-beeznest-com/

These settings might seem complex to setup at first, but once you've created your first instance,
creating a hundred more should be easy.

Important note about file system permissions
-------------

vChamilo instances *need* a central directory where to store all their files. You should create
that directory (as mentioned in point 3 above) and make sure it has the right permissions. 
The plugin/vchamilo/templates/ directory also needs to be writeable by the web server.

Additional notes
-------------

The vChamilo plugin can work in combination with the "Chamilo Shell":https://github.com/chamilo/chamilo-lms 
(or "chash") to ensure a good level of automatization of heavy processes, like upgrading to a higher version.
Although upgrading still has a manual process (in part), it will certainly save you dozens of hours if
upgrading dozens of portals.
