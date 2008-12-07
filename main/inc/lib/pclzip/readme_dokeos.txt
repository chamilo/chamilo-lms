
Additional note from the Dokeos company
December, 2008
------------------------------------------------------


Within Dokeos LMS the PclZip 2.6 library has been integrated for providing
compression and extraction functions for Zip formatted archives.

Also, the file pclzip.lib.php has been upgraded to CVS revision 1.48
(http://phpzip.cvs.sourceforge.net/*checkout*/phpzip/pclzip/pclzip.lib.php?revision=1.48)
in order a known bug to be fixed. See http://www.dokeos.com/forum/viewtopic.php?t=14424 for
detailed information.

The file pclzip.lib_original.php is the original file from the distribution archive
of PclZip 2.6.


Changes
-------

Revision 1.48 
Mon Mar 3 21:57:58 2008 UTC (9 months ago) by vblavet (Vincent Blavet)
Branch: MAIN 
CVS Tags: HEAD 
Changes since 1.47: +35 -21 lines 
Bug correction : When adding files with full windows path (drive letter)
PclZip is now working. Before, if the drive letter is not the default
path, PclZip was not able to add the file.

