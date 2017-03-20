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