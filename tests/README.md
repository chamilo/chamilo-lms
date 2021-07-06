# Chamilo 2.x tests directory

This directory is being used for all kinds of tests and scripts and is removed from
public releases as it may represent a risk for production systems.

## Behat 

Make sure you set the right base_url in behat/behat.yml, then run (on the command
line, from the tests/ directory): 
```
../vendor/behat/behat/bin/behat behat/features/login.feature
../vendor/behat/behat/bin/behat behat/features/createUser.feature
../vendor/behat/behat/bin/behat behat/features/createCourse.feature
../vendor/behat/behat/bin/behat behat/features/courseTools.feature
../vendor/behat/behat/bin/behat behat/features/forum.feature
../vendor/behat/behat/bin/behat behat/features/socialGroup.feature
../vendor/behat/behat/bin/behat behat/features/accessCompanyReports.feature
```

## PHPUnit 

We use the default Symfony PHPUnit settings:

https://symfony.com/doc/current/testing.html

Install phpunit:

`vendor/bin/simple-phpunit --version`

Setup a test database

Create a new env file with named **.env.test.local** with your mysql credentials:

<pre>
DATABASE_HOST='127.0.0.1'
DATABASE_PORT='3306'
DATABASE_NAME='chamilo_test'
DATABASE_USER='root'
DATABASE_PASSWORD='root'
</pre>

After creating the .env.test.local file execute: 

`php bin/console --env=test cache:clear
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
php bin/console --env=test doctrine:fixtures:load --no-interaction`

Those commands will install Chamilo in the chamilo_test database.
You can call the PHPUnit tests with: 

`php bin/phpunit`

If there are DB changes you can migrate your test installation with:

`php bin/console --env=test doctrine:schema:update --force`


## Folders

Although many scripts here are deprecated, the current structure can be
 described as follows;

### behat

Behat-specific folder

### datafiller

Set of scripts to fill your test installation of Chamilo with demo content.

### history

Attempt at keeping a track of what Chamilo looked like over time.

### migrations

Combination of unofficial scripts to execute migrations from other systems

### procedures

xls spreadsheets to be used as base for manual quality review of features in
Chamilo.

### scripts

A collection of scripts used to fix or improve some things globally in Chamilo
portals. Mostly for old versions.
