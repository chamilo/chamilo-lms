=======
Sources
=======

You may export data from various sources:

* PHP Array
* CSV
* Doctrine Query (ORM & ODM supported)
* PDO Statement
* Propel Collection
* PHP Iterator instance
* PHP Iterator with a callback on current
* XML
* Excel XML
* Sitemap (Takes another iterator)
* Chain (can aggregate data from several different iterators)

You may also create your own. To do so, simply create a class that implements ``Exporter\Source\SourceIteratorInterface``.

