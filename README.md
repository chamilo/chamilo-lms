Chamilo LMS
=============

A next generation Learning Management system focused on ease of use, collaboration and sharing.
See documentation/index.html for a complete overview of Chamilo.

Versions differentiation
------------------------

Beware that the Chamilo Association hosts *two completely different applications*: Chamilo LMS (this software here) and Chamilo LCMS (a more experimental application focused on sharing learning objects, mostly, hosted on Bitbucket, not here).
https://campus.chamilo.org, https://stable.chamilo.org and the vast majority of Chamilo installations around the world (98%) use Chamilo LMS.

Inside this Chamilo LMS project itself, there are two main "branches":
* Chamilo LMS 1.9.x offers a stable version of Chamilo that is made more stable with each new version
* Chamilo LMS HEAD (the default if you download it from Github) is in active development and will soon spawn the new 1.10 version (or v10) of Chamilo LMS. It is NOT to be used in production right now.

If you are in search of the latest patches to your production installation, you should choose 1.9.x. If you are adventurous and look forward to contribute to something that *mostly* works but is still under heavy development, you should stick with the default HEAD branch.

Chamilo LMS v10 should be out around January 2014, so not too far away, and comes with an improved files structure and a lot of new dependencies/packages coming from Symfony and Composer. If you have time on your hands and are looking for long term contributions, that's where we'd like you to help.

Installation
------------

You need a working web server + PHP + MySQL setup.

To install from Git (which means installing an unstable, development version of this application), do the following:

* Create a directory where you will store the Chamilo LMS files (beware, the Git repo is about 600MB in size now)
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

Reporting bugs
--------------

Please submit any bugs, feature requests and non-trivial patches to
http://support.chamilo.org/
Always make sure you look for the Chamilo LMS subproject when submittingbug reports, contributing, asking on the forum, IRC, etc.

Contributing
------------

When contributing patches (which we always welcome, as long as you agree to do that under the GNU/GPLv3 license), please ensure you respect our coding conventions: https://support.chamilo.org/projects/1/wiki/Coding_conventions (mostly PSR-2 with a few additional rules and hints).

Manual testing
--------------

You can always check the impact of your changes and confirm with other users on the following portals, which are automatically updated every 15 minutes:
* https://stable.chamilo.org for versions 1.9.x
* https://unstable.chamilo.org for development version (currently 1.10) - this one doesn't automatically apply database changes, so it is more likely to break often
These are *NOT* production portals. Your content *WILL* be deleted once every now and then. It is completely public and anyone can enter and delete your content if they want to. DO NOT put important content there.

Learn more
----------

For news, events and more information on Chamilo LMS please visit
http://www.chamilo.org/

Community
----------

Check out #chamilo on irc.freenode.net.

Visit the official Chamilo Forum: http://www.chamilo.org/forum

License
----------

Chamilo is licensed under the GPLv3 license.

Misc
----

[![Build Status](https://api.travis-ci.org/chamilo/chamilo-lms.png)](https://travis-ci.org/chamilo/chamilo-lms)
