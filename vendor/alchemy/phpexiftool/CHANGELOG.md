CHANGELOG
---------
* Unreleased
  * *No unreleased changes*
  
* 0.7
  * Add timeout to reader and writer (default 60 sec.)

* 0.6.0 (2016-09-29)

  * Add support for Symfony 3 components (@temp)
  * Remove support for old PHP versions (5.3.x, 5.4.X)
  * fix ValueInterface::TYPE_BINARY const typo (@CedCannes)

* 0.5.1 (2016-02-05)

  * Update to exiftool 10.10 which is a production release (@bburnichon)
  * Add support to external ExifTool binary (@gioid)
  * Fix README (@bburnichon & @michalsanger)

* 0.5.0 (2015-11-30)

  * add compatibility up to PHP7 (@bburnichon)
  * all classes generated with included exiftool (10.07) (@bburnichon)
  * add progress bar to command (@bburnichon)
  * Make TagFactory extendable (@bburnichon)
  * Added option "--with-mwg" to classes-builder (@jygaulier)
  * Added a "copy" method in writer: Copy metadata from one file to another (@jygaulier)

* 0.4.1 (2014-09-19)

  * Fix some incompatibilities with exiftool v9.70 (@nlegoff)

* 0.4.0 (2014-09-15)

  * Update to exiftool 9.70
  * Fix type mapping (@SimonSimCity)
  * Fix common args order (@nlegoff)

* 0.3.0 (2013-08-07)

  * Add possibility to erase metadata except ICC profile.
  * Fix sync mode support.
  * Add support for Photoshop preview extraction.

* 0.2.2 (2013-04-17)

  * Add missing files

* 0.2.1 (2013-04-16)

  * Add Tags serialization through JMS Serializer

* 0.2.0 (2013-04-16)

  * Use exiftool 9.15
  * Fix documentation examples

* 0.1.0 (2013-01-30)

  * First stable version.
