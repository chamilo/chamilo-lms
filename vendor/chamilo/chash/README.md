Chamilo Shell script
====================

[![Latest Stable Version](https://poser.pugx.org/chamilo/chash/v/stable.png)](https://packagist.org/packages/chamilo/chash) [![Total Downloads](https://poser.pugx.org/chamilo/chash/downloads.png)](https://packagist.org/packages/chamilo/chash) [![Latest Unstable Version](https://poser.pugx.org/chamilo/chash/v/unstable.png)](https://packagist.org/packages/chamilo/chash) [![License](https://poser.pugx.org/chamilo/chash/license.png)](https://packagist.org/packages/chamilo/chash)

The Chamilo Shell ("Chash") is a command-line PHP tool meant to speed up the management of (multiple)
Chamilo portals under Linux.

Installation
====================

    git clone https://github.com/chamilo/chash.git
    cd chash
    composer install

Note: If you don't have Composer installed, check http://getcomposer.org/download/

Usage
====================

Here are a few examples of how you can use Chash:

In a Chamilo installation folder located in "/var/www/chamilo"

    cd /var/www/chamilo
    php /path/chash/chash.php chamilo:status

    Chamilo $_configuration info:
    Chamilo $_configuration[root_web]: http://localhost/chamilo-1.8.7.1-stable/
    Chamilo $_configuration[root_sys]: /var/www/chamilo-1.8.7.1-stable/
    Chamilo $_configuration[main_database]: chamilo18777_chamilo_main
    Chamilo $_configuration[db_host]: localhost
    Chamilo $_configuration[db_user]: root
    Chamilo $_configuration[db_password]: root
    Chamilo $_configuration[single_database]:
    Chamilo $_configuration[db_glue]: `.`
    Chamilo $_configuration[table_prefix]:

    Chamilo database settings:
    Chamilo setting_current['chamilo_database_version']: 1.9.0.18715
    Chamilo $_configuration[system_version]: 1.9.6


Inside a chamilo folder execute db:sql_cli in order to enter to the SQL client of the Chamilo database:

    php /path/chash.php db:sql_cli --conf=main/inc/conf/configuration.php

If you have configured Chash globally (see below), from any Chamilo directory:

    chash translation:disable french


Building the chash.phar file
====================

This procedure is only required once, and is generally for developers. If you update chash frequently, you'll have to go through this each time you update, but never more than that.

You need to have curl (in order to download packages required to build chash.phar)

    apt-get install php5-curl

If you don't have composer installed on your computer, you can just do the following to download and install it and run the command above (make sure you have PHP5 enabled on the command line):

    curl -sS https://getcomposer.org/installer | php
    php5 composer.phar update --no-dev --prefer-dist

In order to generate the executable chash.phar file. You have to set first this php setting (in your cli php configuration file).

For example in Ubuntu /etc/php5/cli/php.ini

    phar.readonly = Off

(or you can also use the "-d phar.readonly=0" option as described below)

You need to download the required third parties libraries via composer (this might take a few minutes):

    cd chash
    composer update --no-dev --prefer-dist

Then you can call the php createPhar.php file. A new chash.phar file will be created.

In detail:

    cd chash
    composer update --no-dev
    php -d phar.readonly=0 createPhar.php

If you're using php 5.3 with suhosin, the phar will not be executed. You can try this:

    php -d suhosin.executor.include.whitelist="phar" chash.phar

or you can change this setting in your /etc/php5/cli/conf.d/suhosin.ini file
(look for "executor"), although this might increase the vulnerability of your
system. The location of the file may vary depending on your operating system.


Make it global
====================

To get the most out of Chash, you should move the chash.phar file to
(or link from) your /usr/local/bin directory. You can do this getting inside
the directory where you put chash.phar and doing:

    chmod +x chash.phar
    sudo ln -s /path/to/chash.phar /usr/local/bin/chash

Then you can launch chash by moving into any Chamilo installation directory and
typing

    chash

It will give you the details of the commands you can use to run it properly.

The most useful command to us until now has been the "chash db:sql_cli" command,
which puts you directly into a MySQL client session.

Available commands:
====================

    Available commands:
      ccf                                   Shortcut to files:clean_course_files
      dbc                                   Shortcut to db:sql_cli
      dbcli                                 Shortcut to db:sql_cli
      fct                                   Shortcut to files:clean_temp_folder
      fsdu                                  Shortcut to files:show_disk_usage
      fudms                                 Shortcut to files:update_directory_max_size
      help                                  Displays help for a command
      list                                  Lists commands
      selfupdate                            Updates chash to the latest version
      tasl                                  Shortcut to translation:add_sub_language
      tdl                                   Shortcut to translation:disable
      tel                                   Shortcut to translation:enable
      tl                                    Shortcut to translation:list
      tpl                                   Shortcut to translation:platform_language
      urla                                  Shortcut to user:url_access
      usl                                   Shortcut to user:set_language

    chash
      chash:chamilo_install                 Execute a Chamilo installation to a specified version.
      chash:chamilo_status                  Show the information of the current Chamilo installation
      chash:chamilo_upgrade                 Execute a chamilo migration to a specified version or the latest available version
      chash:chamilo_wipe                    Prepares a portal for a new installation
      chash:self-update                     Updates chash to the latest version
      chash:setup                           Setups the migration.yml

    db
      db:drop_databases                     Drops all databases from the current Chamilo install
      db:dump                               Outputs a dump of the database
      db:full_backup                        Generates a .tgz from the Chamilo files and database
      db:restore                            Allows you to restore an SQL dump right into the active database of a given Chamilo installation (which will also erase all previous data in that database)
      db:show_conn_info                     Shows database connection credentials for the current Chamilo install
      db:sql_cli                            Enters to the SQL command line
      db:sql_count                          Count the number of rows in a specific table

    dbal
      dbal:import                           Import SQL file(s) directly to Database.
      dbal:run-sql                          Executes arbitrary SQL directly from the command line.

    files
      files:clean_config_files              Cleans the config files to help you re-install
      files:clean_course_files              Cleans the courses directory
      files:clean_deleted_documents         Cleans the documents that were deleted but left as _DELETED_
      files:clean_temp_folder               Cleans the temp directory.
      files:convert_videos                  Converts all videos found in the given directory (recursively) to the given format, using the ffmpeg command line
      files:delete_courses                  Given an ID, code or category code, deletes one or several courses completely
      files:delete_multi_url                Deletes one URL out of a multi-url campus
      files:generate_temp_folders           Generate temp folder structure: twig
      files:replace_url                     Cleans the config files to help you re-install
      files:set_permissions_after_install   Set permissions
      files:show_disk_usage                 Shows the disk usage vs allowed space, per course
      files:show_mail_conf                  Returns the current mail config
      files:update_directory_max_size       Increases the max disk space for all the courses reaching a certain threshold.
    
    info
      info:which                            Tells where to find code for Chamilo tools

    migrations
      migrations:diff                       Generate a migration by comparing your current database to your mapping information.
      migrations:execute                    Execute a single migration version up or down manually.
      migrations:generate                   Generate a blank migration class.
      migrations:migrate                    Execute a migration to a specified version or the latest available version.
      migrations:status                     View the status of a set of migrations.
      migrations:version                    Manually add and delete migration versions from the version table.

    translation
      translation:add_sub_language          Creates a sub-language
      translation:disable                   Disables a (enabled) language
      translation:enable                    Enables a (disabled) language
      translation:export_language           Exports a Chamilo language package
      translation:import_language           Import a Chamilo language package
      translation:list                      Gets all languages as a list
      translation:platform_language         Gets or sets the platform language
      translation:terms_package             Generates a package of given language terms

    user
      user:change_pass                      Updates the user password to the one given
      user:disable_admins                   Makes the given user admin on the main portal
      user:make_admin                       Makes the given user admin on the main portal
      user:reset_login                      Outputs login link for given username
      user:set_language                     Sets the users language to the one given
      user:url_access                       Show the access per Url

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

Misc
====

[![Build Status](https://api.travis-ci.org/chamilo/chash.png)](https://travis-ci.org/chamilo/chash)
