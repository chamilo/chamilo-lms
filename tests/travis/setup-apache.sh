#!/bin/bash

echo "* Start setup-apache.sh ...";

sudo a2enmod rewrite actions alias

# Use default config
sudo cp -f tests/travis/travis-apache /etc/apache2/sites-available/000-default.conf
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/000-default.conf
sudo chmod 777 -R $HOME

cat /etc/apache2/sites-available/000-default.conf

# Starting Apache
sudo service apache2 restart
