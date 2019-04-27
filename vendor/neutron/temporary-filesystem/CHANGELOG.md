CHANGELOG
=========

* 2.2.0 (2016-03-05)
  * Support Symfony with all 2.x LTS versions and 3.0, add big travis matrix (@patkar)
  * Update php unit to old stable (@patkar)
  * Test with lowest (security release) version and php 5.3 (@patkar)
  * Support PHP 5.6, 7.0, and HHVM and use docker with cache on travis (@patkar)
  * Composer cleanup, ignore lock file (@patkar)

* 2.1.1 (2013-10-10)

  * Add a factory for the Manager

* 2.1.0 (2013-08-06)

  * Use custom IOException instead of Symfony one.
  * Add a TemporaryFilesystemInterface.
  * Add optional directory prefix to createTemporaryDirectory.
  * Add temporary files manager.

* 2.0.1 (2013-04-09)

  * Add TemporaryFilesystem::createTemporaryFile method

* 2.0.0 (2013-04-08)

  * Switch from inheritance to composition design
  * Add TemporaryFilesystem::create method
  * Add TemporaryFilesystem::createTemporaryDirectory method

* 1.0.0 (2012-11-02)

  * First tagged release
