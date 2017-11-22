Maintenance Mode plugin
===

This plugin allows administrators to set the portal in maintenance mode through the change of the .htaccess file located
in the root folder of Chamilo.

As such, it requires the web server (user www-data, httpd or nobody) to temporarily have write access to the .htaccess
file. Maintaining write access on this file is a security vulnerability, so please only set those permissions for the
required time to put your site in maintenance mode, and then return them to non-writable by the web server.