# Chamilo 1.10.x

## Installation

This installation guide is for development environments only.

### Install PHP, a web server and MySQL/MariaDB

To run Chamilo, you will need at least a web server (we recommend Apache2 for commodity reasons), a database server (we recommend MariaDB but will explain MySQL for commodity reasons) and a PHP interpreter (and a series of libraries for it). If you are working on a Debian-based system (Debian, Ubuntu, Mint, etc), just
type
```
sudo apt-get install libapache2-mod-php mysql-server php5-gd php5-intl php5-curl php5-json
```

### Install Git

The development version 1.10.x requires you to have Git installed. If you are working on a Debian-based system (Debian, Ubuntu, Mint, etc), just type
```
sudo apt-get install git
```

### Install Composer

To run the development version 1.10.x, you need Composer, a libraries dependency management system that will update all the libraries you need for Chamilo to the latest available version.

Make sure you have Composer installed. If you do, you should be able to launch "composer" on the command line and have the inline help of composer show a few subcommands. If you don't, please follow the installation guide at https://getcomposer.org/download/

### Download Chamilo from GitHub

Clone the repository

```
sudo mkdir chamilo-1.10
sudo chown -R `whoami` chamilo-1.10
git clone https://github.com/chamilo/chamilo-lms.git chamilo-1.10
```

Checkout branch 1.10.x

```
cd chamilo-1.10
git checkout --track origin/1.10.x
git config --global push.default current
```

### Update dependencies using Composer

From the Chamilo folder (in which you should be now if you followed the previous steps), launch:

```
composer update
```

### Change permissions

On a Debian-based system, launch:
```
sudo chown -R www-data:www-data archive course data home searchdb main/upload/users main/upload/sessions main/upload/courses main/default_course_document/images main/lang main/css main/inc/conf
```

### Start the installer

In your browser, load the Chamilo URL. You should be automatically redirected to the installer. If not, add the "main/install/index.php" suffix manually in your browser address bar. The rest should be a matter of simple OK > Next > OK > Next...

## Upgrade from 1.9.x

1.10.x is a major version. As such, it contains a series of new features, that also mean a series of new database changes in regards with versions 1.9.x. As such, it is necessary to go through an upgrade procedure when upgrading from 1.9.x to 1.10.x.

Although 1.10.x is not beta yet (and as such is *NOT* ready for production and does *NOT* contain all database changes yet - DO NOT UPGRADE A PRODUCTION SYSTEM to 1.10.x yet, PLEASE!), the upgrade procedure works to get you up and running with the latest *development* code of 1.10.x with data from an 1.9.x system, so feel free to test it out, but keep a backup of your database from 1.9.x as you will need to do the upgrade again each time you are updating the 1.10.x code from Git.

The upgrade procedure is relatively straightforward. If you have a 1.9.x initially installed with Git, here are the steps you should follow (considering you are already inside the Chamilo folder):
```
git fetch --all
git checkout origin 1.10.x
```
Then load the Chamilo URL in your browser, adding "main/install/index.php" and follow the upgrade instructions. Select the "Upgrade from 1.8.x" button to proceed.

# Documentation
For more information on Chamilo, visit https://stable.chamilo.org/documentation
