Contributing
------------

Chamilo LMS is a free software learning management platform.

The Chamilo project includes documentation, a mobile app, a
terminal console, integrations with other projects and a series of
related subprojects that are part of the Chamilo ecosystem.

Chamilo LMS is a LAMP-based e-learning web platform that focuses on
providing an easy-to-use environment for teachers to improve both the quality
and availability of their educational material and on providing students with
an awesome learning platform.

Before you report an issue, please check the
[official Chamilo documentation](https://docs.chamilo.org) (not always up to
date).

The Chamilo "editorial" team is small and greatly welcomes any external
contribution. We will thoroughly review them before integration
to make sure they do not introduce security vulnerabilities or degrade the
platform's ease of use, but we do appreciate any sincere effort to help.

Version 2.0 of Chamilo is composed of 2 parts: one legacy part located mostly
in the "public/main" folder, and the main Symfony-based part located in the
"src" folder.

Any contribution to the project should either strive to convert legacy code to
Symfony or to add new features to the Symfony part.

# Installing Chamilo

The following instructions are all intended to set a development environment up
for Chamilo 2.
If you want to install Chamilo on a production server, please refer to the
official installation guide in the documentation/ folder.

## Requirements

### Hardware

We recommend developing Chamilo on a machine with at least
- 8GB of RAM
- 2 powerful CPUs
- about 10GB of disk space

### Software

- Some Linux distribution (tests on Windows server have been limited for development)
- Composer 2.x - https://getcomposer.org/download/
- Yarn 4.x+ - https://yarnpkg.com/getting-started/install
- NodeJS >= v20+ (lts) - https://github.com/nodesource/distributions/blob/master/README.md
- A web server with a virtualhost in a domain or subdomain (not in a subfolder inside a domain with another application).
- A working PHP configuration with PHP 8.2 or 8.3

# Installing Chamilo for development

## Quick step-by-step

You will need PHP8.2 or 8.3 and NodeJS v18+ to run Chamilo 2.

On Ubuntu 24.04+, the following should take care of all dependencies (certbot is optional).

Replace 'chamilo2' by the database name and user you want, and '{password}' by a more secure password.
~~~~
sudo apt update && apt -y upgrade
sudo apt install apache2 libapache2-mod-php mariadb-client mariadb-server redis php-pear php-{apcu,bcmath,cli,curl,dev,gd,intl,ldap,mbstring,mysql,redis,soap,xml,zip} git unzip curl certbot
sudo mysql
mysql> GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY '{password}';
mysql> exit
cd ~
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt-get install -y nodejs
sudo corepack enable
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
cd /var/www
git clone -b master --depth=1 https://github.com/chamilo/chamilo-lms.git chamilo
cd chamilo
composer install
sudo a2enmod rewrite ssl headers expires
sudo cp public/main/install/apache.dist.conf /etc/apache2/sites-available/my.chamilo.net.conf
# edit if you want to change the local domain name
sudo a2ensite my.chamilo.net
sudo systemctl restart apache2
yarn set version stable
yarn up && yarn install && yarn dev
sudo touch .env
sudo chown -R www-data: var/ .env config/
# load http://my.chamilo.net in your browser and follow the installation wizard
sudo chown -R root: .env config/
~~~~

## Detailed procedure

The following is the section above, but with more details and hedge cases.
~~~~
sudo apt update
sudo apt -y upgrade
sudo apt install apache2 libapache2-mod-php mariadb-client mariadb-server redis php-pear php-{apcu,bcmath,cli,curl,dev,gd,intl,ldap,mbstring,mysql,redis,soap,xml,zip} git unzip curl certbot
sudo mysql
mysql> GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY '{password}';
mysql> exit
~~~~

On older Ubuntu versions (like 22.04), you have to install PHP through third-party sources:

~~~~
sudo apt update
sudo apt -y upgrade
sudo apt -y install ca-certificates curl gnupg software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install apache2 libapache2-mod-php8.3 mariadb-client mariadb-server redis php-pear php8.3-{apcu,bcmath,cli,curl,dev,gd,intl,ldap,mbstring,mysql,redis,soap,xml,zip} git unzip curl
sudo mysql
mysql> GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY '{password}';
mysql> exit
~~~~
(replace 'chamilo2' by the database name and user you want, and '{password}' by a more secure password)

## NodeJS, Yarn, Composer

If you already have nodejs installed, check the version with `node -v`
Otherwise, install Node.js 18 or above.

Use the following lines to get a static version of Node.js 20 from https://deb.nodesource.com/ (recommended)
~~~~
cd ~
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt-get install -y nodejs
~~~~

Alternative (not recommended): install Node.js by using NVM (Node Version Manager). You can install it following the
instructions [here](https://github.com/nvm-sh/nvm#installing-and-updating).
You can install the desired node version (preferably, the LTS version):
~~~~
sudo nvm install --lts
sudo nvm use --lts
~~~~

Once NodeJS is installed, you must enable `corepack` and then continue with the requirements
~~~~
sudo corepack enable
cd ~
~~~~

Follow the instructions at https://getcomposer.org/download/ to get Composer, then add it to the local binaries
for easier use:
~~~~
sudo mv composer.phar /usr/local/bin/composer
~~~~

## Apache tweaks

Apache's rewrite module is mandatory if you use Apache. The rest is optional and depends on your needs.
~~~~
sudo apt install libapache2-mod-xsendfile #only for optimization if used in the vhost config
sudo a2enmod rewrite ssl headers expires
sudo systemctl restart apache2
~~~~

When your system is all set, you can use the following:
~~~~
cd /var/www
git clone -b master --depth=1 https://github.com/chamilo/chamilo-lms.git chamilo
cd chamilo
composer install
~~~~

We do not recommend running composer as the root user!
When asked whether you want to execute the recipes or install plugins for some of the components,
you can safely type 'n' (for 'no').

~~~~
yarn set version stable
# delete yarn.lock if present, as it might contain restrictive packages from a different context
yarn up && yarn install && yarn dev
# you can safely ignore any "warning" mentioned by yarn dev
sudo touch .env
sudo chown -R www-data: var/ .env config/
~~~~

In your web server configuration, ensure you allow for the interpretation of .htaccess (`AllowOverride all` or
`Require all granted`), and point the `DocumentRoot` to the `public/` subdirectory.
Finally, make sure your PHP config points at Redis for sessions management.
This should look similar to the short excerpt below (in your Apache vhost block) if you use SSL.
If you do not use SSL, you can remove the first block and change `*:443` by `*:80` for the second block.
~~~~
<VirtualHost *:80>
  ServerName my.chamilo.net
  RewriteEngine On
  RedirectMatch 302 (.*) https://my.chamilo.net$1
</VirtualHost>
<VirtualHost *:443>
  DocumentRoot /var/www/chamilo/public/
  ServerName my.chamilo.net
  # Error logging rules...
  # SSL rules...
  RewriteEngine On
  <Directory /var/www/chamilo/public>
    AllowOverride All
    Require all granted
  </Directory>
  <LocationMatch "/.git">
    Require all denied
  </LocationMatch>
  php_value session.cookie_httponly 1
  php_admin_value session.save_handler "redis"
  php_admin_value session.save_path "tcp://127.0.0.1:6379"
  php_admin_value upload_max_filesize 256M
  php_admin_value post_max_size 256M
</VirtualHost>
~~~~
Don't forget to reload your Apache configuration after each change:
~~~~
sudo systemctl reload apache2
~~~~

## Quick updates for development/testing purposes

If you have already installed it and just want to update it from Git, do:
~~~~
git pull origin master
composer install
# php bin/console doctrine:schema:update --force --complete (only recommended if you installed before beta 1)
php bin/console cache:clear
yarn install && yarn dev
~~~~

The commands above will update the JS (yarn) in public/build/ and PHP (composer) dependencies in vendor/.

Note for developers in alpha stage: the doctrine command will try to update
your database schema to the expected database schema in a fresh installation.

This is not always perfect, as Doctrine will take the fastest route to do this.

For example, if you have a migration to rename a table (which would apply just
fine to a system in Chamilo 1 being *migrated*), Doctrine might consider that
the destination table does not exist and the original (which should not be
there in a new installation) is still there, so it will just drop the old
table and create a new one, losing all records in that table in the process.

To avoid this, prefer executing migrations with the following instead.
```
php bin/console doctrine:migrations:execute "Chamilo\CoreBundle\Migrations\Schema\V200\Version[date]"
```
This will respect the migration logic and do the required data processing.
You can see the version numbers in the list of updated or created files when launching `git pull`.

Som`e updates might (rarely) cause conflicts with existing files so, to avoid those, here are some hints :
- for composer errors, you can remove the vendor folder and composer.lock file, then launch `composer update`
- for yarn errors, you can remove yarn.lock .yarn/cache/* node_modules/* and launch `yarn up`
- when opening Chamilo, if the page does not load, then you might want to delete var/cache/* or launch `php bin/console cache:clear` from the root of Chamilo

### Refresh configuration settings

In case you believe some settings in Chamilo might not have been processed
correctly based on an incomplete migration or a migration that was added
after you installed your development version of Chamilo, the
/admin/settings_sync URL is built to try and fix that automatically by updating
PHP classes based on the database state. This issue rarely happens, though.

## Quick re-install

If you have it installed in a dev environment and feel like you should clean it
up completely (might be necessary after changes to the database), you can do so
by:

* Removing the `.env` file
* Loading the {url}/main/install/index.php page again

The database should be automatically destroyed, table by table. In some extreme
cases (a previous version created a table that is not necessary anymore and
creates issues), you might want to clean it completely by just dropping the
database, but this shouldn't be necessary most of the time.

If, for some reason, you have issues with either composer or yarn, a good first
step is to delete completely the `vendor/` folder (for composer) or the
`node_modules/` folder (for yarn).

## Development setup (Dev environment, stable environment not yet available)

If you are a developer and want to contribute to Chamilo in the current
development branch (not stable yet), then please follow the instructions below.
Please bear in mind that the development version is NOT STABLE at this time,
and many features are just not working yet. This is because we are working on
root components that require massive changes to the structure of the code,
files and database. As such, to get a working version, you might need to
completely uninstall and re-install from time to time. You've been warned.

First, apply the procedure described here:
[Managing CSS and JavaScript in Chamilo](assets/README.md) (in particular,
make sure you follow the given links to install all the necessary components
on your computer).

Then make sure your database supports large prefixes
(see [this Stack Overflow thread](https://stackoverflow.com/questions/43379717/how-to-enable-large-index-in-mariadb-10/43403017#43403017)
if you use MySQL < 5.7 or MariaDB < 10.2.2).

Load the (your-domain)/main/install/index.php URL to start the installer (which
is very similar to the installer in previous versions).

If the installer is pure-HTML and doesn't appear with a clean layout, that's
probably because you didn't follow these instructions carefully.
Go back to the beginning of this section and try again.

If you want hot reloading for assets use the command `yarn run encore dev-server`.
This will refresh automatically your assets when you modify them under
`assets/vue`. Access your chamilo instance as usual. In the background, this
will serve assets from a custom server on http://localhost:8080. Do not access
this url directly since [Encore](https://symfony.com/doc/current/frontend.html#webpack-encore)
is in charge of changing url assets as needed.

### Supporting PHP 7.4 and 8.3 in parallel

You might want to support PHP 8.3 (for Chamilo 2) and PHP 7.4 (for all other
things) on the same server simultaneously. On Ubuntu, you could do it this way:
```
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.3 libapache2-mod-php7.4 php8.3-{modules} php7.4-{modules}
sudo apt remove libapache2-mod-php8.3 php7.4-fpm
sudo a2enmod proxy_fcgi
sudo vim /etc/apache2/sites-available/[your-chamilo2-vhost].conf
```

In the vhost configuration, make sure you set PHP 8.3 FPM to answer this single
vhost by adding, somewhere between your `<VirtualHost>` tags, the following:
```
  <IfModule !mod_php8.c>
    <IfModule proxy_fcgi_module>
        <IfModule setenvif_module>
        SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
        </IfModule>
        <FilesMatch ".+\.ph(ar|p|tml)$">
            SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
        </FilesMatch>
        <FilesMatch ".+\.phps$">
            Require all denied
        </FilesMatch>
        <FilesMatch "^\.ph(ar|p|ps|tml)$">
            Require all denied
        </FilesMatch>
    </IfModule>
  </IfModule>
```

Then exit and restart Apache:
```
sudo systemctl restart apache2
```

Finally, remember that PHP settings will have to be changed in
`/etc/php/8.3/fpm/php.ini` and you will have to reload `php8.3-fpm` to take
those config changes into account.
```
sudo systemctl reload php8.3-fpm
```

When using 2 versions, you will also have issues when calling
`composer update`, as this one needs to be called by the relevant PHP version.
This can be done like so:
```
/usr/bin/php8.3 /usr/local/bin/composer update
or, for Chamilo 1.11
/usr/bin/php7.4 /usr/local/bin/composer update
```
If your default php-cli uses PHP7.4 (see `ln -s /etc/alternatives/php`),
you might have issues running with a so-called `platform_check.php` script
when running `composer update` anyway. This is because this script doesn't
user the proper launch context, and you might need to change your default
settings on Ubuntu (i.e. change the link /etc/alternatives/php to point to
the other php version) before launching `composer update`. You can always
revert that operation later on if you need to go back to work on Chamilo 1.11
and Composer complains again.

### git hooks

To use the git hook sample scripts under `tests/scripts/git-hooks/`, the
following commands can be used.

    git config core.hooksPath tests/scripts/git-hooks/

## Big changes from 1.x (for developers)

This is a list of structural changes to help developers/maintainers of Chamilo 1.11 find their way in Chamilo 2. It is *not* a features list (refer to `public/documentation/changelog.html` for that).

* in general, the `main/` folder has been moved to `public/main/`
* a big part of the frontend has been migrated to VueJS + Tailwind CSS (see `assets/vue/`)
* `app/Resources/public/assets/` moved to `assets/`
* `main/inc/lib/javascript/` moved to `assets/js/`
* `main/img/` moved to `public/img/`
* `main/template/default/` moved to `src/CoreBundle/Resources/views/`
* `src/Chamilo/XXXBundle/` moved to `src/CoreBundle/` or `src/CourseBundle/`
* `bin/doctrine.php` removed, use `bin/console doctrine:xyz` options
* plugin images, CSS and JS libs are loaded inside the `public/plugin/` folder and the folder names have been renamed to CamelCase
* plugins templates use the `asset()` function instead of using `_p.web_plugin`
* `main/inc/local.inc.php` has been removed and `main/inc/global.inc.php` greatly reduced
* translations are managed through Gettext, from the `translations/` directory for PHP code and from `assets/locales/` for VueJS code
* files in `app/config/` have been restructured (to `.yaml`) and moved to `config/`
* `app/config/configuration.php` has essentially been emptied to the `settings` table (accessible via the admin page in Chamilo), while the critical settings (database access etc) have been transferred to `.env`

### Libraries

* Integration with Symfony 6
* PHPMailer replaced with Symfony Mailer
* Bower replaced by [yarn](https://yarnpkg.com)


# Contributing patches or new features

If you'd like to contribute to this project, please read the following documents:

* [Coding conventions][1]: The main conventions document
* [PSR-1][2]: PSR-1 are standard conventions rules we use as a base (conversion of old code still in progress)
* [PSR-2][3]: PSR-2 are more detailed standard conventions rules we use as base (conversion of old code still in progress)

We expect contributions to be sent through Pull Requests, a very clean feature
of our versioning control platform.
We recommend you follow this guide to understand a little more about the way
it works: https://guides.github.com/activities/contributing-to-open-source/

In short, we ask you to send us Pull Requests based on a branch that you create
with this purpose into your own repository, forked from the original Chamilo repository (`master` branch).

## Testing new features

As new major features are added, automated tests should be added that ensure
that the feature continues to work for the foreseeable future.

In Chamilo, we rely on "Behat":http://docs.behat.org/en/latest/ to do
Automated Behavior Testing. You can find examples and information on how to
run Behat tests in the tests/behat/ folder of your Chamilo installation.

Tests are run automatically for every new contribution, courtesy of Github
actions, so you can follow your feature in time and see whether something
breaks it.
Check the Chamilo tests URL here: https://github.com/chamilo/chamilo-lms/actions

# Making changes to the database

If your changes require database changes, here are a few instructions on how to
proceed. You will then need to submit these changes as explained above.

## Database structure changes

If your changes are about structure, you want to follow these steps:
1. Create or modify an entity in src/*something*Bundle/Entity/
2. Create a new Migration in src/CoreBundle/Migrations/Schema/*something*/

This second step is most easily done by copying one of the current migration
files in that directory. For example, if you're doing it on the 14th of July 2019 at noon:
1. Copy Version20190527120703.php to Version20190714120000.php
2. Edit the file and change any "20190527120703" you find to "20190714120000"
3. Check it works by issuing an update command from the command line:
```
php bin/console migrations:execute 20190714120000 --up --configuration=app/config/migrations.yml
```

## Database data changes

If you only want to change the *data* in the database, then you don't need to
modify or create an entity, but you will still need to follow these two steps:
1. Modify one of the fixtures in `src/CoreBundle/DataFixtures/`
2. Create a new Migration in `src/Chamilo/CoreBundle/Migrations/Schema/{version}/`

For configuration settings, check https://github.com/chamilo/chamilo-lms/wiki/Add-a-new-Chamilo-setting

# Testing through Docker

Coming soon...


[1]: https://github.com/chamilo/chamilo-lms/wiki/Coding-conventions
[2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[3]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
