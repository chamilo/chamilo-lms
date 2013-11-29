CHANGELOG
---------

* 0.4.1 (10-21-2013)

  * Add compatibility with PHP-FFMpeg 0.4.
  * Fix temporary files management.

* 0.4.0 (07-04-2013)

  * Add compatibility with PHP-FFMpeg 0.3.
  * BC Break : Implementation must now explicitely registered MediaVorus
    mimetype guessers as they are not automatically registered anymore.
  * Ensure FFProbe dependency is optional.

* 0.3.2 (04-16-2013)

  * Cleanup JMS Serializer / exiftool / PHPExiftool version dependencies

* 0.3.1 (01-31-2013)

  * Merge with 0.1.2
  * Register FileBinaryMimeTypeGuesser prior to FileInfoMimetypeGuesser

* 0.3.0 (01-14-2013)

  * Add support for media serialization via JMS serializer.

* 0.2.1 (12-21-2012)

  * Support of the latest PHPExiftool API.

* 0.2.0 (12-14-2012)

  * Update API.
  * Add support of videos Width/Height via FFProbe.

* 0.1.2 (02-07-2013)

  * Add more mime types to match the document format.

* 0.1.1 (12-14-2012)

  * Support for Video Width / Height extraction via FFProbe.

* 0.1.0 (11-26-2012)

  * First stable version.
