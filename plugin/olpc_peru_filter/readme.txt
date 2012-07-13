This plugin will create administration settings to configure the content filtering on OLPC Peru's XS servers (uses Squid).

In order to test this plugin, you should have a working version of the Squid
proxy system as well as a series of directories and files. Here is an example
on how to generate a fake structure that will work with the default plugin
config (it is *not* a secure way to do it, though, so don't use in production):
sudo mkdir /var/sqg
sudo mkdir /var/squidGuard
sudo mkdir /var/squidGuard/blacklists
sudo mkdir /var/squidGuard/blacklists/Games
sudo touch /var/sqg/blacklists
sudo chmod -R 0777 /var/sqg
sudo chmod -R 0777 /var/squidGuard/blacklists

After that, enable the plugin, then go to some course's config screen and
check/uncheck the "Games" option. Now check that it updated the 
/var/sqg/blacklists file... That's all folks!

The blacklists in /var/squidGuard/blacklists/ can be downloaded from http://dsi.ut-capitole.fr/blacklists/index_en.php
