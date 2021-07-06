# Chamilo 2.x

[![PHP Composer](https://github.com/chamilo/chamilo-lms/workflows/PHP%20Composer/badge.svg)](https://github.com/chamilo/chamilo-lms/actions?query=workflow%3A%22PHP+Composer%22+branch%3Amaster)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/?branch=master)
[![Bountysource](https://www.bountysource.com/badge/team?team_id=12439&style=raised)](https://www.bountysource.com/teams/chamilo?utm_source=chamilo&utm_medium=shield&utm_campaign=raised)
[![Code Consistency](https://squizlabs.github.io/PHP_CodeSniffer/analysis/chamilo/chamilo-lms/grade.svg)](http://squizlabs.github.io/PHP_CodeSniffer/analysis/chamilo/chamilo-lms/)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/166/badge)](https://bestpractices.coreinfrastructure.org/projects/166)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/88e934aab2f34bb7a0397a6f62b078b2)](https://www.codacy.com/app/chamilo/chamilo-lms?utm_source=github.com&utm_medium=referral&utm_content=chamilo/chamilo-lms&utm_campaign=badger)

Chamilo is an e-learning platform, also called "LMS" or "LCMS" published under GNU/GPLv3+. It is or has been used by more than 20M people worldwide.

## Quick install

**Chamilo 2.0 is still in development stage. This installation procedure is for reference only. For a stable Chamilo, please install Chamilo 1.11.x. See the 1.11.x branch README.md for details.**

We assume you already have: 

- composer 2.x - https://getcomposer.org/download/
- yarn 2.x - https://yarnpkg.com/getting-started/install
- Configuring a virtualhost in a domain, not in a sub folder inside a domain.
- A working LAMP server.

On a fresh Ubuntu, you can prepare your server by issuing an apt command like the following:

~~~~
apt update && apt -y upgrade && apt install apache2 libapache2-mod-php mariadb-client mariadb-server php-pear php-dev php-gd php-curl php-intl php-mysql php-mbstring php-zip php-xml php-cli php-apcu php-bcmath php-soap yarn git unzip npm
~~~~

Otherwise, you can use the following directly:

~~~~
git clone https://github.com/chamilo/chamilo-lms.git chamilo2
cd chamilo2
composer install
# *important*: when composer asks to accept recipes, about 11 times, press enter or "n"
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
yarn set version berry
yarn install
yarn run encore dev
chmod -R 777 .
~~~~

Note: on Ubuntu Groovy, the `yarn` package has been replaced by `yarnpkg`. In this case, replace `yarn` by `yarnpkg` in all commands above.

Then enter the **main/install/index.php** and follow the UI instructions (database, admin user settings, etc).

After the web install process, change the permissions back to a reasonably safe state:
~~~~
chmod -R 755 .
chown -R www-data: public/ var/
~~~~

### Quick update

If you have already installed it and just want to update it from Git, do:
~~~~
git pull
composer update

# Database update
php bin/console doctrine:schema:update --force
    
# js/css update
yarn install
yarn run encore dev
~~~~
This will update the JS (yarn) and PHP (composer) dependencies in the public/build folder.

### Quick re-install

If you have it installed in a dev environment and feel like you should clean it up completely (might be necessary after changes to the database), you can do so by:

* Removing the `.env.local`
* Load the {url}/main/install/index.php script again

The database should be automatically destroyed, table by table. In some extreme cases (a previous version created a table that is not necessary anymore and creates issues), you might want to clean it completely by just dropping it, but this shouldn't be necessary most of the time.

## Installation guide (Dev environment, stable environment not yet available)

If you are a developer and want to contribute to Chamilo in the current development branch (not stable yet), 
then please follow the instructions below. Please bear in mind that the development version is NOT COMPLETE at this time, 
and many features are just not working yet. This is because we are working on root components that require massive changes to the structure of the code, files and database. As such, to get a working version, you might need to completely uninstall and re-install from time to time. You've been warned.

First, apply the procedure described here: [Managing CSS and JavaScript in Chamilo](assets/README.md) (in particular, make sure you follow the given links to install all the necessary components on your computer).

Then make sure your database supports large prefixes (see [this Stack Overflow thread](https://stackoverflow.com/questions/43379717/how-to-enable-large-index-in-mariadb-10/43403017#43403017) if you use MySQL < 5.7 or MariaDB < 10.2.2).

Load the (your-domain)/main/install/index.php URL to start the installer (which is very similar to the installer in previous versions). 
If the installer is pure-HTML and doesn't appear with a clean layout, that's because you didn't follow these instructions carefully. 
Go back to the beginning of this section and try again.
 
## Changes from 1.x

* app/Resources/public/assets moved to public/assets
* main/inc/lib/javascript moved to public/js
* main/img/ moved to public/img
* main/template/default moved to src/CoreBundle/Resources/views
* src/Chamilo/XXXBundle moved to src/CoreBundle or src/CourseBundle
* bin/doctrine.php removed use bin/console doctrine:xyz options
* Plugin images, css and js libs are loaded inside the public/plugins folder
  (composer update copies the content inside plugin_name/public inside web/plugins/plugin_name
* Plugins templates use asset() function instead of using "_p.web_plugin"
* Remove main/inc/local.inc.php

Libraries 

* Integration with Symfony 5 
* PHPMailer replaced with Swift Mailer
* bower replaced by [yarn](https://yarnpkg.com)

## JWT Authentication

* php bin/console lexik:jwt:generate-keypair
* In Apache setup Bearer with:

`SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1`

Get the token:

`curl -k -X POST -H "Content-Type: application/json" https://example.com/api/authentication_token -d '{"username":"admin","password":"admin"}'`

The response should return something like:

`{"token":"MyTokenABC"}`

Go to:

https://example.com/api

Click in "Authorize" and write

`Bearer MyTokenABC`

Then you can make queries using the JWT token.

## Todo

See https://github.com/chamilo/chamilo-lms/projects/3

## Contributing

If you want to submit new features or patches to Chamilo 2, please follow the
Github contribution guide https://guides.github.com/activities/contributing-to-open-source/
and our [CONTRIBUTING.md](CONTRIBUTING.md) file.
In short, we ask you to send us Pull Requests based on a branch that you create
with this purpose into your repository forked from the original Chamilo repository.

## Documentation

For more information on Chamilo, visit https://campus.chamilo.org/documentation/index.html

## Notes

You can install Yarn on Ubuntu following the instructions at https://linuxize.com/post/how-to-install-yarn-on-ubuntu-18-04/
You can install Composer on Ubuntu following the instructions at https://getcomposer.org/download/
