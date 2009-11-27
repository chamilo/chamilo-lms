Dokeos Search Plugin Installation Guide
=======================================

1. Introduction
---------------
This search plugin is composed of everything you need to get up and
running with a full-text search feature on your Dokeos portal. However,
this installation is not easy, and if you are not familiar with the
term "indexing", or with the configuration of a Linux server, we
highly recommend you seek advice from a qualified system administrator
to help you doing this. Of course, the Dokeos company, for which I am
directly working, offers this kind of services. Feel free to contact
info@dokeos.com for a quote.

This search plugin relies on a search server, called MnogoSearch, which
has to be installed independently on a Linux server (the Windows
version, sadly, is not GPL nor free to use).
The following installation guides you through the steps of installing
the server on a Debian or Ubuntu computer, but you might probably
succeed in installing it on other architectures.

Dokeos has made considerable efforts to have MnogoSearch integrated
into the latest versions of PHP, but it would never have succeeded
without the help of pierre.php@gmail.com who did all the technical
work.

2. Installing files
--------------------
All the "conf" files and the "search.xml.php" file in this package
need to be revised to configure properly. Most of all, you should look
for a "DBAddr  mysql://db_user:db_pass@db_host/db_name/?dbmode=single"
line in the server files to make sure it is using the correct database
credentials.

Now you will see that there are two directories in this plugin.
The "client" directory needs to stay there. The "client/www" directory
contains a PHP script that needs to be copied at the root of your
Dokeos portal (this will later give the indexing server an access to
your Dokeos portal).

The "server" directory has to be moved on the indexing server (which
might be the same as your Dokeos portal's server if it is not too
overloaded).

This "server" directory contains three subdirectories.
The "server/etc" directory contains the configuration of the
mnogosearch server, which typically on Debian will be located in
/etc/mnogosearch. Once you have installed the mnogosearch server,
you can pretty much overwrite the configuration with the files
contained in "server/etc", as they are already customised for indexing
Dokeos.

The "server/cron.d" directory contains an optional file that you might
want to put in /etc/cron.d, so that the indexing will be run every night
5.00am.

The "server/www" directory contains files that should be made available
to the public, to access idexation results. Feel free to put these, for
example, in /var/www/mnogosearch on your indexing server if that's where
Apache takes its public files.

3. Installing the search server (MnoGoSearch)
---------------------------------------------
The mnogosearch server installation comes in two parts:
A) installing the mnogosearch indexing server itself. This can be done
with a simple:
  sudo apt-get install mnogosearch-common mnogosearch-mysql
B) installing the PHP5-mnogosearch bindings. This can be done by using
the PECL command-line installer
  sudo pecl install mnogosearch-1.0.0

Once the server is installed, you may need to install server specific
additional programs to allow your indexer to go into documents (PDF,
Word, Excel, etc) and index the contents of these documents as well.

You can find a list of programs supposed to be there in the
server/etc/indexer.conf file. Search for "pdftotext" and you will find
the lines nearby all define a program used to translate a document
into pure text before indexing it. Make sure you are able to launch
all of these commands on the command line. If you can't, the indexing
server is not likely to be able to do it either...

4. Creating the DB and Dokeos user
----------------------------------
In order to keep the index data, mnogosearch requires a database to
store this data. It is recommended to create an alternative user, with
access to only one database to do this.

Once this user is configured and the DBAddr line is configured in
server/etc/indexer.conf, you can create the database structure by
calling (on the indexing server):
 indexer -Ecreate indexer.conf

The next step is to create a Dokeos user for the purpose of indexing
your courses (the user needs access to all courses to be able to index
them). Create a simple user in the Dokeos administration interface. Then
get his ID (you can get it by hovering the edition icon in the users
list: the user id is the number that shows after "user_id" in the URL)
and use it inside indexer_login.php to replace the 'xxx' value.

Also configure the IP address and the host name of the indexing server
inside this file.

Once these two steps are complete, you can start the first indexation
of your portal, by calling, on the command line of your indexing server:
  indexer -N10 index.conf
N10 lets you limit the number of simultaneous threads that your indexing
server will be allowed to use. More than 10 might put your Dokeos portal
in overload. You might want to reduce this number to 3 for light servers.

5. Installing the plugin
------------------------
Installing the plugin is done by dispatching the files contained in
this plugin as described in "2. Installing files", and configuring the
various *.conf.php files as well as server/etc/indexer.conf and
server/www/search.xml.php

Once the files have been moved and configured, you will still need to
index some data, then activate the plugin inside the Dokeos
administration panel. Then, basically, you should be able to use
the plugin straight away.

6. International use
--------------------
To keep this plugin small, we had to remove a considerable amount of
international-parsing helper files. If you need one for your language,
it may well be included in the default installation file for the
Debian mnogosearch-common package.

If not, you should check more recent versions of mnogosearch on its
website: http://www.mnogosearch.org/

7. Seek help
------------
Commercial suppport is available for the configuration and remote use
of this plugin at info@dokeos.com
If you have plenty of time to learn it by yourself or any other reason,
you might find some free help on our forums: http://www.dokeos.com/forum