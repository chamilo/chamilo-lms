CHANGELOG
---------

* 0.4.0 (06-25-2013)

  * BC Break : Transcoder::create arguments have been inverted.
  * BC Break : Service provider configuration has been updated.

* 0.3.0 (04-24-2013)

  * Use Alchemy\BinaryDriver as driver base.
  * BC Break : remove methods `open` and `close`. Methods `toImage` and `toPDF`
    now take the input file as first argument.
  * Code cleanup

* 0.2.0 (02-01-2013)

  * Update API, BC break : rename PDFTranscoder to Transcoder

* 0.1.1 (11-27-2012)

  * First stable version.
