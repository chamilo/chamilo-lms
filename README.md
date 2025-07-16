# Chamilo 2

[![Behat tests 🐞](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml)
[![PHPUnit 🐛](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml)
[![PHP static analysis ✨](https://github.com/chamilo/chamilo-lms/actions/workflows/php_analysis.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/php_analysis.yml)
[![PHP format code 🔎](https://github.com/chamilo/chamilo-lms/actions/workflows/format_code.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/format_code.yml)
[![codecov](https://codecov.io/gh/chamilo/chamilo-lms/branch/master/graph/badge.svg?token=46YggfLZnY)](https://codecov.io/gh/chamilo/chamilo-lms)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/?branch=master)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/166/badge)](https://bestpractices.coreinfrastructure.org/projects/166)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/88e934aab2f34bb7a0397a6f62b078b2)](https://www.codacy.com/app/chamilo/chamilo-lms?utm_source=github.com&utm_medium=referral&utm_content=chamilo/chamilo-lms&utm_campaign=badger)
[![type-coverage](https://shepherd.dev/github/chamilo/chamilo-lms/coverage.svg)](https://shepherd.dev/github/chamilo/chamilo-lms/coverage.svg)
[![psalm level](https://shepherd.dev/github/chamilo/chamilo-lms/level.svg)](https://shepherd.dev/github/chamilo/chamilo-lms/level.svg)

Chamilo is an e-learning platform, also called "LMS", published under the GNU/GPLv3+ license. It has been used by more than 30M people worldwide since its inception in 2010. This is a development version. For the current stable branch, please select the 1.11.x branch in the Code tab.

## Quick install

**Chamilo 2.0 is still in development. The installation procedure below is for reference only. For a stable Chamilo, please install Chamilo 1.11.x. See the 1.11.x branch's README.md for details.**

### Minimum hardware requirements

#### Server

You will need (short version):
- 2 vCPUs
- 4GB RAM
- 4GB free disk space

Chamilo 2.0 has been tested on a 2 vCPUs, 2GB RAM virtual machine under Ubuntu 24.04 and has been shown to work, but to
build the development environment, you will need at least 4GB RAM.
At this stage, we haven't made any load testing to evaluate the number of users that could use the system simultaneously.
Remember this is an alpha version. As such, it will run in "dev" mode (see the `.env` file), considerably more slowly the "prod" mode.

#### Client

Any recent computer with a recent (no older than 5y) browser should do.

### Software stack requirements

You should have:

- Some Linux distribution (tests on Windows server have been very limited, with mitigated success)
- Composer 2.x - https://getcomposer.org/download/
- Yarn 4.x+ - https://yarnpkg.com/getting-started/install
- NodeJS >= v18+ (lts) - https://github.com/nodesource/distributions/blob/master/README.md
- A web server with a virtualhost in a domain or subdomain (not in a sub folder inside a domain with another application).
- A working LAMP/WAMP server with PHP 8.2 or 8.3
- A Redis server (for storing PHP sessions)

### Software stack install (Ubuntu)

#### Installing from a packaged version

These are local testing instructions for a fictitious domain name "my.chamilo.net" configured in your hosts file and
pointing at a local machine's IP address. These instructions do NOT provide a secure environment.

Spawn an Ubuntu Server 24.04 LTS Virtual Machine. Login as root (or `sudo -s` once connected) through SSH.
Install the software stack and Chamilo using the commands below.

~~~~
apt update && apt -y upgrade
apt install -y apache2 libapache2-mod-php mariadb-client mariadb-server redis php-pear php-{apcu,bcmath,cli,curl,dev,gd,intl,mbstring,mysql,redis,soap,xml,zip} git unzip curl certbot
mysql -e "GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY 'chamilo2';"
cd /var/www && wget https://github.com/chamilo/chamilo-lms/releases/download/v2.0.0-alpha.2/chamilo-2.0.0-alpha.2.tar.gz
tar zxf chamilo-2.0.0-alpha.2.tar.gz
mv chamilo-2.0.0-alpha.2 chamilo
cd chamilo
cp public/main/install/apache.dist.conf /etc/apache2/sites-available/my.chamilo.net.conf
a2ensite my.chamilo.net
a2enmod rewrite ssl headers expires
systemctl restart apache2
# Open http://my.chamilo.net in your browser to go through the installation wizard
# Complete the installation information using DB credentials chamilo2/chamilo2/chamilo2 and the default host and port
# Done
~~~~

By default, it is installed in "dev" mode to have more debugging features at hand. If you want to change it to "prod"
mode, you will have to install yarn (see below), run `yarn encore prod` and modify the `.env` file at the root of
your chamilo folder to change `APP_ENV` to `'prod'` instead of the default `'dev'`. See other installation methods below.

#### Installing from sources

##### Quick step-by-step

You will need PHP8.2 or 8.3 and NodeJS v18+ to run Chamilo 2.

On Ubuntu 24.04+, the following should take care of all dependencies (certbot is optional).

Replace 'chamilo2' by the database name and user you want, and '{password}' by a more secure password.
~~~~
sudo apt update && apt -y upgrade
sudo apt install apache2 libapache2-mod-php mariadb-client mariadb-server redis php-pear php-{apcu,bcmath,cli,curl,dev,gd,intl,mbstring,mysql,redis,soap,xml,zip} git unzip curl certbot
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

##### Detailed procedure

The following is the section above, but with more details and hedge cases.
~~~~
sudo apt update
sudo apt -y upgrade
sudo apt install apache2 libapache2-mod-php mariadb-client mariadb-server redis php-pear php-{apcu,bcmath,cli,curl,dev,gd,intl,mbstring,mysql,redis,soap,xml,zip} git unzip curl certbot
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
sudo apt install apache2 libapache2-mod-php8.3 mariadb-client mariadb-server redis php-pear php8.3-{apcu,bcmath,cli,curl,dev,gd,intl,mbstring,mysql,redis,soap,xml,zip} git unzip curl
sudo mysql
mysql> GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY '{password}';
mysql> exit
~~~~
(replace 'chamilo2' by the database name and user you want, and '{password}' by a more secure password)

#### NodeJS, Yarn, Composer

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

#### Apache tweaks

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

### Web installer

Once the above is ready, use your browser to load the URL you have defined for your host, e.g. https://my.chamilo.net
(this should redirect you to `main/install/index.php`) and follow the UI instructions (database, admin user settings, etc).

After the web install process, change the permissions back to a reasonably safe state:
~~~~
chown -R root: .env config/
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

Libraries

* Integration with Symfony 6
* PHPMailer replaced with Symfony Mailer
* Bower replaced by [yarn](https://yarnpkg.com)

## JWT Authentication

This version of Chamilo allows you to use a JWT (token) to use the Chamilo API
more securely. In order to use it, you will have to generate a JWT token as
follows.

* Run
  ```shell
  php bin/console lexik:jwt:generate-keypair
  ```
* In Apache setup Bearer with:
  ```apacheconf
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
  ```
* Get the token:
  ```shell
  curl -k -X POST https://example.com/api/authentication_token \
      -H "Content-Type: application/json" \
      -d '{"username":"admin","password":"admin"}'
  ```
  The response should return something like:
  ```json
  {"token":"MyTokenABC"}
  ```
* Go to https://example.com/api
* Click in "Authorize" button and write the value
`Bearer MyTokenABC`

Then you can make queries using the JWT token.

## Todo

See https://github.com/chamilo/chamilo-lms/projects/3

## Contributing

If you want to submit new features or patches to Chamilo 2, please follow the
Github contribution guide https://guides.github.com/activities/contributing-to-open-source/
and our [CONTRIBUTING.md](CONTRIBUTING.md) file.

In short, we ask you to send us Pull Requests based on a branch that you create
with this purpose into your own repository, forked from the original Chamilo repository (`master` branch).

## Documentation

For more information on Chamilo 2, visit https://2.chamilo.org/documentation/index.html
