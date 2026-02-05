# Chamilo 2

[![Behat tests 🐞](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/behat.yml)
[![PHPUnit 🐛](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml/badge.svg)](https://github.com/chamilo/chamilo-lms/actions/workflows/phpunit.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chamilo/chamilo-lms/?branch=master)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/166/badge)](https://bestpractices.coreinfrastructure.org/projects/166)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/88e934aab2f34bb7a0397a6f62b078b2)](https://www.codacy.com/app/chamilo/chamilo-lms?utm_source=github.com&utm_medium=referral&utm_content=chamilo/chamilo-lms&utm_campaign=badger)
[![type-coverage](https://shepherd.dev/github/chamilo/chamilo-lms/coverage.svg)](https://shepherd.dev/github/chamilo/chamilo-lms/coverage.svg)
[![psalm level](https://shepherd.dev/github/chamilo/chamilo-lms/level.svg)](https://shepherd.dev/github/chamilo/chamilo-lms/level.svg)
[![DPG Badge](https://img.shields.io/badge/Verified-DPG%20(Since%20%202024)-3333AB?logo=data:image/svg%2bxml;base64,PHN2ZyB3aWR0aD0iMzEiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMSAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE0LjIwMDggMjEuMzY3OEwxMC4xNzM2IDE4LjAxMjRMMTEuNTIxOSAxNi40MDAzTDEzLjk5MjggMTguNDU5TDE5LjYyNjkgMTIuMjExMUwyMS4xOTA5IDEzLjYxNkwxNC4yMDA4IDIxLjM2NzhaTTI0LjYyNDEgOS4zNTEyN0wyNC44MDcxIDMuMDcyOTdMMTguODgxIDUuMTg2NjJMMTUuMzMxNCAtMi4zMzA4MmUtMDVMMTEuNzgyMSA1LjE4NjYyTDUuODU2MDEgMy4wNzI5N0w2LjAzOTA2IDkuMzUxMjdMMCAxMS4xMTc3TDMuODQ1MjEgMTYuMDg5NUwwIDIxLjA2MTJMNi4wMzkwNiAyMi44Mjc3TDUuODU2MDEgMjkuMTA2TDExLjc4MjEgMjYuOTkyM0wxNS4zMzE0IDMyLjE3OUwxOC44ODEgMjYuOTkyM0wyNC44MDcxIDI5LjEwNkwyNC42MjQxIDIyLjgyNzdMMzAuNjYzMSAyMS4wNjEyTDI2LjgxNzYgMTYuMDg5NUwzMC42NjMxIDExLjExNzdMMjQuNjI0MSA5LjM1MTI3WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg==)](https://digitalpublicgoods.net/r/chamilo)

Chamilo is an e-learning platform (also called "LMS" for Learning Management
System) published under the GNU/GPLv3+ license. It has been used by more than
40M people worldwide since its start in 2010.

Chamilo offers a wide range of features, including:
- Announcements
- API for integration with other systems (HR, SIS, ERP, e-commerce, ...)
- Assignments (create, hand in, grade, co-grade with AI, integration with plagiarism check, ...)
- Attendance tracking (take attendance, Qualiopi reporting, multilevel attendance, signing, ...)
- Calendar events (with reminders and subscriptions)
- Content Management System capabilities (CMS: manage pages, menus)
- Course administration (create, edit, delete, publish, export, import from Moodle, ...)
- Course catalogue (e-commerce module, course search, catalogue filtering by user groups)
- Documents (including integration with OnlyOffice for documents viewing and collaboration)
- E-learning multimedia modules support (video, 360° video, audio, images, ...)
- File sharing (upload, download, share, ...)
- Forum (create, post, reply, peer review, ...)
- Learning paths (create, manage, ...)
- Live chat (at course level or global, including AI chatbot)
- GDPR compliance (GDPR-ready, export of personal data, ...)
- Gradebook (including generation of badges and certificates with QR codes)
- Learning analytics (progress, course completion, participation, average time spent, average score, auditing, ...)
- Groups/Classes (at course or global level)
- Multilingual support (60+ languages fully translated, including RTL support)
- Plugins for advanced features
- Quizzes (20+ question types, random selection with categories, adaptative tests, time limits, co-creation with AI, proctoring tools integration, ...)
- Roles and permissions management (beta)
- SCORM 1.2, QTI, LTI, xAPI CMI 5, Aiken, and other standards compliant formats
- Security features (password policy and rotation, 2FA/MFA authentication, HSTS, regular updates, ...)
- Sessions management (re-use courses multiple times, add structure to long-term courses management chaos)
- Skills management (create, edit, delete, assign to users, scale/levels of acquisition, ...)
- Student profiles (edit personal data, change password, subscribe to push notifications, ...)
- Style customization (easy color changes, custom CSS, custom logo, ...)
- Surveys (create, take, analyse)
- Upgrades from previous versions and import of existing courses from other LMS
- Videoconference through integrations / Realtime collaboration
- ...

Note: AI features (with support for OpenAI, Grok, Gemini, Claude and DeepSeek
models) and other integrations do require active subscriptions to, or
availability of, external services/applications.

## Try it out

You can try out Chamilo at https://campus.chamilo.net/ (use the "Teach courses" option to give yourself creation rights).

## Quick install

**IMPORTANT** Chamilo 2.0 is in its validation phase right now.
The installation procedure below is for reference only.
For a stable Chamilo, please install Chamilo 1.11.x.
See the 1.11.x branch's README.md for details.

### Minimum hardware requirements

#### Server

You will need:
- 2 vCPUs
- 4GB RAM
- 4GB free disk space

Chamilo 2.0 has been tested on a 2 vCPUs, 2GB RAM virtual machine under Ubuntu 24.04 and has been shown to work, but to
build the development environment, you will need at least 4GB RAM.
At this stage, we haven't made any load testing to evaluate the number of users that could use the system simultaneously.
Remember this is an alpha version. As such, it will run in "dev" mode (see the `.env` file), considerably more slowly the "prod" mode.

#### Client

Any recent computer with a recent (no older than 5y) browser should do.

### Minimal software stack requirements

You should have:

- A web server with a virtualhost in a domain or subdomain (not in a subfolder inside a domain with another application).
- A working PHP configuration with PHP 8.2 or 8.3
- MariaDB 10 or higer (alternatively, MySQL 5.7 or higher can also be used)

### Software stack installation (Ubuntu)

You can install Chamilo using 3rd party installers like Softaculous,
Installatron, DigitalOcean marketplace, etc. and skip the following steps.

These are instructions for a fictitious `my.chamilo.net` domain, with a
`chamilo2` database and DB user, on your own self-managed server.
Please adapt the commands below accordingly.

These instructions are meant for a standalone public server, with no
additional component needed. These do not include setting up SSL certificates.
We leave the latter to you.

If using VMs, spawn an Ubuntu Server (24.04 LTS or later) VM.
Login through SSH with admin permissions through `sudo`. Otherwise, just use
the `sudo` command as needed on your own server.
Install the software stack and Chamilo using the commands below.

~~~~
sudo apt update && sudo apt -y upgrade
sudo apt install -y apache2 libapache2-mod-php mariadb-client mariadb-server php-{apcu,bcmath,cli,curl,dev,gd,intl,ldap,mbstring,mysql,redis,soap,xml,zip} unzip curl
sudo mysql -e "GRANT ALL PRIVILEGES ON chamilo2.* TO chamilo2@localhost IDENTIFIED BY 'chamilo2';"
cd /var/www
sudo wget https://github.com/chamilo/chamilo-lms/releases/download/v2.0.0-RC.1/chamilo-2.0.0-RC.1.tar.gz
sudo tar zxf chamilo-2.0.0-RC.1.tar.gz
sudo mv chamilo-2.0.0-RC.1 chamilo
cd chamilo
sudo cp public/main/install/apache.dist.conf /etc/apache2/sites-available/my.chamilo.net.conf
# Edit /etc/apache2/sites-available/my.chamilo.net.conf to match your domain
sudo a2ensite my.chamilo.net
sudo a2enmod rewrite ssl headers expires
sudo chown -R www-data: .env config/ var/
sudo systemctl restart apache2
# Open http://my.chamilo.net in your browser to go through the installation wizard
# Complete the installation information using DB credentials chamilo2/chamilo2/chamilo2 and the default host and port
# Done
~~~~

Note: PHP's LDAP extension is only required if you need to connect to LDAP or a
compatible system, or if you want to install Chamilo from sources (see
CONTRIBUTING.md), but in principle it should not add any overhead to the
system if you don't use it.

#### Installing from sources

See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Web installer

Once the above is ready, use your browser to load the URL you have defined for your host, e.g. https://my.chamilo.net
(this should redirect you to `main/install/index.php`) and follow the UI instructions (database, admin user settings, etc).

After the web install process, change the permissions back to a reasonably safe state:
~~~~
chown -R root: .env config/
~~~~

Your Chamilo is ready to use!

## Documentation

For more information on Chamilo 2, visit https://2.chamilo.org/documentation/index.html
For Chamilo usage documentation, most of the documentation at
https://docs.chamilo.org/ is still valid for Chamilo 2, despite having been
written for Chamilo 1.

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

## Contributing

If you want to submit new features or patches to Chamilo 2, please follow the
Github contribution guide https://guides.github.com/activities/contributing-to-open-source/
and our [CONTRIBUTING.md](CONTRIBUTING.md) file.

In short, we ask you to send us Pull Requests based on a branch that you create
with this purpose into your own repository, forked from the original Chamilo repository (`master` branch).
