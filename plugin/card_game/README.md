Card reveal game Chamilo plugin
===============================
This plugin adds a little game to the interface to encourage users to connect
every day.

# Installation

To install, enable the plugin, then go to "Regions" in the administration panel
and add the region "pre_footer" to the plugin. Save.
The plugin should appear as a little icon in the lower-right side of your user
picture in the left column of the "My courses" list.

## Migrating from a non-official version
This plugin was initially designed by _Les Compagnons Bâtisseurs_. If you had
used it before its review and integration into Chamilo, you will need to
execute the following changes in your database to update its structure.

##### Database changes
You need execute these SQL queries in your database if you are upgrading to 
Chamilo 1.11.8 and the card_game plugin was already installed in your previous version.

```sql
ALTER TABLE plugin_card_game CHANGE COLUMN idUser user_id INT NOT NULL;
ALTER TABLE plugin_card_game CHANGE COLUMN dateAcces access_date date default NULL;

```