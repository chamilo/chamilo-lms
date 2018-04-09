# Chamilo 2.x

[![Build Status](https://travis-ci.org/chamilo/chamilo-lms.svg?branch=master)](https://travis-ci.org/chamilo/chamilo-lms)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/?branch=master)
[![Bountysource](https://www.bountysource.com/badge/team?team_id=12439&style=raised)](https://www.bountysource.com/teams/chamilo?utm_source=chamilo&utm_medium=shield&utm_campaign=raised)
[![Code Consistency](https://squizlabs.github.io/PHP_CodeSniffer/analysis/chamilo/chamilo-lms/grade.svg)](http://squizlabs.github.io/PHP_CodeSniffer/analysis/chamilo/chamilo-lms/)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/166/badge)](https://bestpractices.coreinfrastructure.org/projects/166)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/88e934aab2f34bb7a0397a6f62b078b2)](https://www.codacy.com/app/chamilo/chamilo-lms?utm_source=github.com&utm_medium=referral&utm_content=chamilo/chamilo-lms&utm_campaign=badger)

## Installation

The installation instructions are located in the documentation here:
[Installation](documentation/installation_guide.html)

## Upgrade
The upgrade instructions are located in the documentation here:
 [Upgrade](app/Resources/docs/upgrade.md)
 
## Dev environment install guide

The instructions above are meant only for stable versions. If you are a developer and want to contribute to Chamilo in the current development branch (not stable yet), then please follow the instructions below. Please bear in mind that the development version is NOT COMPLETE at this time, and many features are just not working yet. This is because we are working on root components that require massive changes to the structure of the code, files and database. As such, to get a working version, you might need to completely uninstall and re-install from time to time. You've been warned.

First, apply the procedure described here: [Managing CSS and JavaScript in Chamilo](assets/README.md) (in particular, make sure you follow the given links to install all the necessary components on your computer).

Then make sure your database supports large prefixes (see [this Stack Overflow thread](https://stackoverflow.com/questions/43379717/how-to-enable-large-index-in-mariadb-10/43403017#43403017) if you use MySQL < 5.7 or MariaDB < 10.2.2).

Load the (your-domain)/main/install/ URL to start the installer (which is very similar to the installer in previous versions). If the installer is pure-HTML and doesn't appear with a clean layout, that's because you didn't follow these instructions carefully. Go back to the beginning of this section and try again.

Finally, if you are installing this development version in a subdirectory, you will need to add "folder" in configuration.php 
once the installation process finished:
```
$_configuration['url_append'] = 'the-folder';
```
and in the ".env" file in the root folder:
```
APP_URL_APPEND=the-folder
```

## Changes from 1.x

* app/Resources/public/assets moved to public/assets
* main/inc/lib/javascript moved to public/js
* main/img/ moved to public/img
* main/template/default moved to src/Chamilo/CoreBundle/Resources/views
* bin/doctrine.php removed use bin/console doctrine:xyz options
* PHPMailer replaced with Swift Mailer
* Plugin images, css and js libs are loaded inside the public/plugins folder
  (composer update copies the content inside plugin_name/public inside web/plugins/plugin_name
* Plugins templates use asset() function instead of using "_p.web_plugin"
* bower replaced by [yarn](https://yarnpkg.com)


## Todo
* Auth (CAS, Shibboleth, Oath2)
* URL course changes "cidReq" to "c", "session_id" to "s"
* Fix plugins that use api_get_setting directly in the code
* Fix plugins render using tpl or PHP files

## Contributing

If you want to submit new features or patches to Chamilo, please follow the
Github contribution guide https://guides.github.com/activities/contributing-to-open-source/
and our CONTRIBUTING.md file.
In short, we ask you to send us Pull Requests based on a branch that you create
with this purpose into your repository forked from the original Chamilo repository.

## Documentation

For more information on Chamilo, visit https://1.11.chamilo.org/documentation/index.html
