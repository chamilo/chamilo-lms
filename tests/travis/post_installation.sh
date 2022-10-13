#!/bin/bash

echo "* post_installation.sh ...";

sudo chmod 777 app/config/configuration.php
sudo echo "\$_configuration[\"disable_send_mail\"] = true;" >> app/config/configuration.php
sudo cat app/config/configuration.php