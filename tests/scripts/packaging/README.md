Git log script to pre-generate changelog
========================================

This directory only hosts a script to pre-generate the changelog for Chamilo.
To work properly, it requires you to download Yannick Warnier's version of
the php-git wrapper by the witty Sebastian Bergmann (author of PHP Unit, along
other things): `git clone https://github.com/ywarnier/git php-git`.

This should give you all you need to then launch 
`php gitlog.php > /tmp/changelog` 
and then copy-paste it to the Chamilo changelog (it still requires a careful
manual review, but it already saves *a lot* of time).

Usage:
 - `php gitlog.php -t` indicates the script has to return datestamps
 - `php gitlog.php -max20170625` stops when reaching a date previous to this date
 - `php gitlog.php [commit-hash]` stops when finding the given commit hash in the commits history
