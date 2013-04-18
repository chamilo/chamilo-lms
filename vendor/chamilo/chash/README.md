Chamilo Shell script
====================

The Chamilo Shell (or "Chash") is a command-line PHP tool meant to speed up the
management of (multiple) Chamilo portals under Linux.

To get the most out of Chash, you should move the chash.phar file to your
/usr/local/bin directory. You can do this getting inside the directory where
you put chash.phar and doing:

    chmod +x chash.phar
    sudo ln -s /path/to/chash.phar /usr/local/bin/chash

Then you can launch chash by moving into any Chamilo installation directory and
typing

    chash

It will give you the details of what command you can use to run it properly.

The most useful command to us until now has been the "chash database:sql" command,
which puts you directly into a MySQL client session.

Building the chash.phar file
====================

In order to generate the executable chash.phar file. You have to set first this php setting (in your cli php configuration file)

    phar.readonly = Off

Then you can call the php createPhar.php file. A new chash.phar file will be created.

Remember to add execution permissions to the phar file.

 Example:

    cd chash
    php -d phar.readonly=0 createPhar.php
    chmod +x chash.phar
    sudo ln -s /path/to/chash.phar /usr/local/bin/chash
    Then you can call the chash.phar file in your Chamilo installation

    cd /var/www/chamilo
    chash
    
If you're using php 5.3 with suhosin the phar will not be executed you can try this:

    php -d suhosin.executor.include.whitelist="phar" chash.phar 

or you can change this setting in your /etc/php5/cli/conf.d/suhosin.ini file (look for "executor"), although this might increase the vulnerability of your system.

Available commands:
====================

    db
        db:drop_databases       Drops all databases from the current Chamilo install
        db:dump                 Outputs a dump of the database
        db:full_backup          Generates a .tgz from the Chamilo files and database
        db:restore              Allows you to restore an SQL dump right into the active database of a given Chamilo installation (which will also erase all previous data in that database)
        db:show_conn_info       Shows database connection credentials for the current Chamilo install
        db:sql_cli              Enters to the SQL command line
        db:sql_count            Count the number of rows in a specific table

    files
        files:clean_archives          Cleans the archives directory
        files:clean_config_files      Cleans the config files to help you re-install
        files:show_mail_conf          Returns the current mail config

    translation
        translation:export_language   Exports a Chamilo language package
        translation:import_language   Import a Chamilo language package
        translation:platform_language Gets or sets the platform language

    user
        user:change_pass              Updates the user password to the one given
        user:disable_admins           Makes the given user admin on the main portal
        user:make_admin               Makes the given user admin on the main portal
        user:reset_login              Outputs login link for given username
        user:set_language             Sets the users language to the one given

Usage
====================

Inside a chamilo folder execute db:sql_cli in order to enter to the SQL client of the Chamilo database:

    chash.phar db:sql_cli


Licensing
=========

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Mail: info@chamilo.org
