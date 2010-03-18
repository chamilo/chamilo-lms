
Supplemental "readme" from the Chamilo team about integration
of PclZip 2.8.2 library within Chamilo 1.8.7 LMS
February 25, 2010
--------------------------------------------------------------------------------

Applied patches:

1. A patch about the renamed function gzopen() as gzopen64()
in Ubuntu Karmic Koala 9.10 (php5 5.2.10.dfsg.1-2ubuntu6).
http://dokeoslead.wordpress.com/2009/09/30/pclzip-and-gzopen64/
https://bugs.launchpad.net/ubuntu/+source/php5/+bug/451405
http://php.net/manual/en/function.gzopen.php

2. A patch about compatibility with arcives created with Windows-based
utilities. It is for successfull reading of stored filenames with backslash
directory separator (Windows style) within such an archives.
The problem has been detected with IZArc 3.81 (possibly new versions too)
http://www.izarc.org/
For detail see http://support.chamilo.org/issues/627
