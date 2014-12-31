Chamilo LMS
=============

What is Chamilo LMS?
--------------------

An advanced Learning Management system focused on ease of use and speed.
See documentation/index.html for a complete overview of Chamilo.

Versions differentiation
------------------------

Beware that the Chamilo Association supports *two completely different applications*: Chamilo LMS (this software here) and Chamilo LCMS (a more experimental application focused on sharing learning objects, mostly, hosted on Bitbucket, not here).
https://campus.chamilo.org, https://stable.chamilo.org and the vast majority of Chamilo installations around the world (98%) use Chamilo LMS.

Inside this Chamilo LMS project itself, there are two main "branches":
* Chamilo LMS 1.9.x is a stable version of Chamilo that is made more stable with each new version
* Chamilo LMS HEAD (the default if you download it from Github) is in active development and will soon spawn the new v2 version of Chamilo LMS. It is a general effort to reuse base components from other sources instead of maintaining near-deprecated packages). It should NOT to be used in production at this point.

If you are in search of the latest patches to your production installation, you should choose 1.9.x. If you are an adventurous developer and look forward to contribute to something that *mostly* works but is still under heavy development, you might try with the default HEAD branch.

Chamilo LMS v2 should be available in beta version around early 2015, so not too far away, and comes with an improved files structure and a lot of new dependencies/packages coming from Symfony and Composer. If you have time on your hands and are looking for long term contributions, that's where we'd like you to help.

# Chamilo v1.9.x

### Requirements

Chamilo LMS supports PHP 5.4 and up, but we recommend PHP 5.5 (and up) with the
Zend Optimizer+ (opcache module) enabled for greater efficiency.

Chamilo requires MariaDB or MySQL v5.1 or higher.

Chamilo has been reported to work under Linux, Windows and Mac OSes.

Chamilo 1.9 has been reported to work with Apache 2 and Nginx. IIS installations
have been reported to work too, but testing has been insufficient to guarantee 
stability.

### Installation

To install from Git (which means installing an unstable, development version of this application), do the following:

* Create a directory where you will store the Chamilo LMS files (beware, the Git repo is about 1GB in size now)
* Install git (if that's not already the case)
* Clone the Chamilo LMS repo from Github:
```
git clone https://github.com/chamilo/chamilo-lms.git the-directory-you-created
```

Once you have downloaded it, you will need to follow the installation instructions. You can get the latest version inside the documentation/ folder of your recently-downloaded Chamilo, or you can see them online for the latest *stable* version at https://stable.chamilo.org/documentation/installation_guide.html

Before you start the installation procedure, if you want to work on Chamilo LMS 1.9.x, you'll need to do this:
```
cd the-directory-you-created
git checkout --track origin/1.9.x
git config --global push.default current
```

This way, you'll stick to the 1.9.x branch only in this directory (your installation will be 1.9.x only), and when sending commits, they will automatically be sent to the 1.9.x branch.

Finally, if you are really looking into contributing back to Chamilo, you should (really) create yourself a Github account and "fork this project". You would then download Chamilo from your own Github repository first, then send changes to your repository and finally (once you're sure they're matching our coding conventions), submit a "Pull request" to Chamilo. This is the cleanest, more time-saving way to do it!

# Chamilo v2

This version is *not* stable. It is not even alpha yet. Only for developers and
testing.

### Requirements

Chamilo LMS supports PHP 5.4 and up, but we recommend PHP 5.6 (and up). 

Chamilo requires MariaDB or MySQL v5.1 or higher.

Chamilo has been reported to work under Linux, Windows and Mac OSes.

### Installation

Command-line install:

```
git clone https://github.com/chamilo/chamilo-lms.git chamilo
cd chamilo
composer update
php app/console chamilo:install --force --drop-database
```

Browser install:

```
git clone https://github.com/chamilo/chamilo-lms.git chamilo
cd chamilo
composer update
```

Load localhost/chamilo/install.php in your browser and follow the instructions.

Behat scenarios
-------------

See the /features folder

Then download [Selenium Server](http://seleniumhq.org/download/), and run it:

```
java -jar selenium-server-standalone-2.44.0.jar
```

Create a virtual host with this name "my.chamilo_test.net"
This virtual host is use inside the /behat.yml file

Run your scenario using the behat console:

```
bin/behat
```

Documentation
-------------

A teachers guide is available in English and Spanish from [our website][1]
An admin guide is available in English from [our support website][2]
A developers guide for 1.9.x currently being written in English is available from [our "docs" repository][3]


Reporting bugs
--------------

Please submit any bugs, feature requests and non-trivial patches to
http://support.chamilo.org/
Always make sure you look for the Chamilo LMS subproject when submittingbug reports, contributing, asking on the forum, IRC, etc.

Contributing
------------

When contributing patches (which we always welcome, as long as you agree to do that under the GNU/GPLv3 license), please ensure you respect our [coding conventions][4] (mostly PSR-2 with a few additional rules and hints).

Before you contribute, you should consider carefully the branch to which you want to contribute. The "master" branch (the default) is the continuously experimental branch of Chamilo, so by nature it is unstable and it is *not* used in production. The "1.9.x" branch (or the highest number ending with an ".x") is the currently stable branch. New releases are *tags* that are set on the stable branch when a new version is released. So, if you are looking to contribute on a bug of 1.9.8 in prevision for 1.9.9, you should use branch 1.9.x.

We gladly welcome Pull Requests on GitHub, so if you feel like you have 30 minutes and can contribute a patch, fork our repo, create a branch and send a PR (probably against branch 1.9.x). We will review it before the next release. Although we are generally fast enough at reviewing PRs, sometimes we might be more busy than others, so please be patient with us. Ultimately, we *will* review your PR and include it if it's useful and it follows our coding conventions (see link above).

Manual testing
--------------

You can always check the impact of your changes and confirm with other users on the following portals, which are automatically updated every 15 minutes:
* https://stable.chamilo.org for versions 1.9.x
* https://unstable.chamilo.org for development version (currently v2) - this one doesn't automatically apply database changes, so it is more likely to break often
These are *NOT* production portals. Your content *WILL* be deleted once every now and then. It is completely public and anyone can enter and delete your content if they want to. DO NOT put important content there.

Automated testing
-----------------

We have a few automated tests written in SimpleTest but, after a series of unsuccessful attempts at developing the right set of tests covering 100% of the code, we decided to give up and rewrite an important part of Chamilo's legacy code. This is what v2, our current master branch, is about (between other things).

You can find the existing tests in the tests/ directory in any clone generated from GitHub (you won't find it in the downloadable archive on our website, though).

Learn more
----------

For news, events and more information on Chamilo LMS please visit
[http://www.chamilo.org](http://www.chamilo.org) 

Community
----------

Check out #chamilo on irc.freenode.net.

Visit the official Chamilo Forum: [http://www.chamilo.org/forum](http://www.chamilo.org/forum) 

License
----------

Chamilo is licensed under the GPLv3 license.

Misc
----

[1]: http://www.chamilo.org/en/documentation
[2]: https://support.chamilo.org/issues/5653
[3]: https://github.com/chamilo/docs/tree/master/1.9/en/developer
[4]: https://support.chamilo.org/projects/1/wiki/Coding_conventions

[![Build Status](https://api.travis-ci.org/chamilo/chamilo-lms.png)](https://travis-ci.org/chamilo/chamilo-lms)
