Contributing
------------

Chamilo LMS is a free software, community-driven project.

Chamilo LMS is an LAMP-based e-learning web platform that focuses on providing an easy-to-use
environment for teachers to improve both the quality and availability of their
educational material, and on providing students with an awesome learning platform.

We are a relatively small development team and greatly welcome any contribution
from the outside world, although we will thoroughly review them before integration,
to make sure they do not introduce security vulnerabilities or degrade the ease
of use of the platform.

The 1.10.x (that you're currently looking at) is a development branch for the 
1.10.0 release, that we hope to be releasing around mid-2015. It is a 
transitional version that partially uses a series of Symfony 2 modules but relies
heavily on Composer to manage the dependencies towards common libraries. Version 
2.0 has already been worked a lot on, and has served as an inspiration for 1.10.0,
but due to the huge success of the 1.9.x series, we decided to take a transitional
step towards 2.0 to ensure a smooth migration of all our user base to the newer
version (to be released sometime in 2016).

# Contributing patches or new features

If you'd like to contribute to this project, please read the following document:

* [Coding conventions][1]: The main conventions document
* [PSR-1][2]: PSR-1 are standard conventions rules we use as a base (conversion of old code still in progress)
* [PSR-2][3]: PSR-2 are more detailed standard conventions rules we use as base (conversion of old code still in progress)

In short, we expect contributions to be sent through Pull Requests, a very clean feature of Github.
We recommend you follow this guide to understand a little more about the way it works: 
https://guides.github.com/activities/contributing-to-open-source/

# Making changes to the database

If your changes require database changes, here are a few instructions on how to
proceed. You will then need to submit these changes as explained above.

## Database structure changes

If your changes are about structure, you want to follow these steps:
1. Create or modify an entity in src/Chamilo/CoreBundle/Entity/
2. Create a new Migration in src/Chamilo/CoreBundle/Migrations/Schema/V110/

This second step is most easily done by copying one of the current migration
files in that directory. For example, if you're doing it on the 14th of July 2015 at noon:
1. Copy Version20150527120703.php to Version20150714120000.php
2. Edit the file and change any "20150527120703" you find to "20150714120000"
3. Check it works by issuing an update command from the command line:
```
php bin/doctrine.php migrations:execute 20150714120000 --up --configuration=app/config/migrations.yml
```

## Database data changes

If you only want to change the *data* in the database, then you don't need to 
modify or create an entity, but you will still need to follow these two steps:
1. Modify the main/install/data.sql file (at the end, add a new section before the chamilo_database_version update
2. Create a new Migration in src/Chamilo/CoreBundle/Migrations/Schema/V110/ (see above section for details)



[1]: https://support.chamilo.org/projects/chamilo-18/wiki/Coding_conventions
[2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[3]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
