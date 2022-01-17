# Chamilo 2

[![Behat tests 🐞](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml)
[![PHPUnit 🐛](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml)
[![PHP static analysis ✨](https://github.com/chamilo/chamilo-lms/actions/workflows/php_analysis.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/php_analysis.yml)
[![PHP format code 🔎](https://github.com/chamilo/chamilo-lms/actions/workflows/format_code.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/format_code.yml)
[![codecov](https://codecov.io/gh/chamilo/chamilo-lms/branch/master/graph/badge.svg?token=46YggfLZnY)](https://codecov.io/gh/chamilo/chamilo-lms)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/?branch=master)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/166/badge)](https://bestpractices.coreinfrastructure.org/projects/166)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/88e934aab2f34bb7a0397a6f62b078b2)](https://www.codacy.com/app/chamilo/chamilo-lms?utm_source=github.com&utm_medium=referral&utm_content=chamilo/chamilo-lms&utm_campaign=badger)

Chamilo is an e-learning platform, also called "LMS", published under the GNU/GPLv3+ license. It has been used by more than 30M people worldwide since its inception in 2010. This is a development version. For the current stable branch, please select the 1.11.x branch in the Code tab.

## Quick install

**Chamilo 2.0 is still in development. This installation procedure is for reference only. For a stable Chamilo, please install Chamilo 1.11.x. See the 1.11.x branch's README.md for details.**

We assume you already have:

- composer 2.x - https://getcomposer.org/download/
- yarn +2.x - https://yarnpkg.com/getting-started/install
- Node >= v16+ (lts) - https://github.com/nodesource/distributions/blob/master/README.md
- Configuring a virtualhost in a domain, not in a sub folder inside a domain.
- A working LAMP/WAMP server with PHP 8.0+

### Software stack install (Ubuntu)

You will need PHP8+ and NodeJS v14+ to run Chamilo 2.
On a fresh Ubuntu 21.04, you can prepare your server by issuing an apt command like the following with sudo (or as root, but not recommended for security reasons):

~~~~
sudo apt update
sudo apt -y upgrade
sudo apt -y install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install apache2 libapache2-mod-php mariadb-client mariadb-server php-pear php-dev php-gd php-curl php-intl php-mysql php-mbstring php-zip php-xml php-cli php-apcu php-bcmath php-soap git unzip
cd ~
curl -sL https://deb.nodesource.com/setup_16.x -o nodesource_setup.sh
sudo bash nodesource_setup.sh
sudo apt install nodejs
sudo corepack enable
cd ~
# follow the instructions at https://getcomposer.org/download/
sudo mv composer.phar /usr/local/bin/composer
# optionally, you might want this:
sudo apt install libapache2-mod-xsendfile
sudo a2enmod rewrite ssl headers expires
sudo systemctl restart apache2
~~~~

When your system is all set, you can use the following:

~~~~
cd /var/www
git clone https://github.com/chamilo/chamilo-lms.git chamilo2
cd chamilo2
composer install
# not recommended to do this as the root user!
# when asked whether you want to execute the recipes for some of the components, you can safely say no.

yarn set version stable
yarn install
yarn run encore dev
chmod -R 777 .
~~~~

In your web server configuration, ensure you allow for the interpretation of .htaccess (`AllowOverride all` and `Require all granted`), and point the `DocumentRoot` to the `public/` subdirectory.

### Web installer

Once the above is ready, enter the **main/install/index.php** and follow the UI instructions (database, admin user settings, etc).

After the web install process, change the permissions back to a reasonably safe state:
~~~~
chmod -R 755 .
chown -R www-data: public/ var/
~~~~

## Quick update

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

### Refresh configuration settings

In case you believe some settings in Chamilo might not have been processed correctly based on an incomplete migration
or a migration that was added after you installed your development version of Chamilo, the URL /admin/settings_sync is
built to try and fix that automatically by updating PHP classes based on the database state.
This issue rarely happens, though.

## Quick re-install

If you have it installed in a dev environment and feel like you should clean it up completely (might be necessary after changes to the database), you can do so by:

* Removing the `.env.local`
* Load the {url}/main/install/index.php script again

The database should be automatically destroyed, table by table. In some extreme cases (a previous version created a table that is not necessary anymore and creates issues), you might want to clean it completely by just dropping it, but this shouldn't be necessary most of the time.

If, for some reason, you have issues with either composer or yarn, a good first step is to delete completely the `vendor/` folder (for composer) or the `node_modules/` folder (for yarn).

## Development setup (Dev environment, stable environment not yet available)

If you are a developer and want to contribute to Chamilo in the current development branch (not stable yet),
then please follow the instructions below. Please bear in mind that the development version is NOT COMPLETE at this time,
and many features are just not working yet. This is because we are working on root components that require massive changes to the structure of the code, files and database. As such, to get a working version, you might need to completely uninstall and re-install from time to time. You've been warned.

First, apply the procedure described here: [Managing CSS and JavaScript in Chamilo](assets/README.md) (in particular, make sure you follow the given links to install all the necessary components on your computer).

Then make sure your database supports large prefixes (see [this Stack Overflow thread](https://stackoverflow.com/questions/43379717/how-to-enable-large-index-in-mariadb-10/43403017#43403017) if you use MySQL < 5.7 or MariaDB < 10.2.2).

Load the (your-domain)/main/install/index.php URL to start the installer (which is very similar to the installer in previous versions).
If the installer is pure-HTML and doesn't appear with a clean layout, that's because you didn't follow these instructions carefully.
Go back to the beginning of this section and try again.

### Supporting PHP 7.4 and 8.0 in parallel

Because PHP 8.0 is relatively new, you might want to support PHP 8.0 (for Chamilo 2) and PHP 7.4 (for all other things) on the same server simultaneously. On Ubuntu, you could do it this way:
```
add-apt-repository ppa:ondrej/php
apt update
apt install php8.0 libapache2-mod-php7.4
apt remove libapache2-mod-php8.0 php7.4-fpm
a2enmod proxy_fcgi
vim /etc/apache2/sites-available/[your-chamilo2-vhost].conf
```

In the vhost configuration, make sure you set PHP 8.0 FPM to answer this single vhost by adding, somewhere between your `<VirtualHost>` tags, the following:
```
  <IfModule !mod_php8.c>
    <IfModule proxy_fcgi_module>
        <IfModule setenvif_module>
        SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
        </IfModule>
        <FilesMatch ".+\.ph(ar|p|tml)$">
            SetHandler "proxy:unix:/run/php/php8.0-fpm.sock|fcgi://localhost"
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
systemctl restart apache2
```

Finally, remember that PHP settings will have to be changed in /etc/php/8.0/fpm/php.ini and you will have to reload php8.0-fpm to take those config changes into account.
```
systemctl reload php8.0-fpm
```

## Changes from 1.x

* in general, the main/ folder has been moved to public/main/
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
* PHPMailer replaced with Symfony Mailer
* bower replaced by [yarn](https://yarnpkg.com)

## JWT Authentication

* php bin/console lexik:jwt:generate-keypair
* In Apache setup Bearer with:

`SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1`

Get the token:

```
curl -k -X POST -H "Content-Type: application/json" https://example.com/api/authentication_token -d '{"username":"admin","password":"admin"}'
```

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
