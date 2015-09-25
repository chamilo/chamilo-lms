CHANGELOG
=========

A [BC BREAK] means the update will break the project for many reasons:

* new mandatory configuration
* new dependencies
* class refactoring

### 2015-06-29

* [XmlSourceIterator] Add possibility to set custom tag names

### 2015-06-09

* [BC BREAK] Replaced deprecated `PropertyAccess::getPropertyAccessor()` method `PropertyAccess::createPropertyAccessor()`.
             Symfony 2.2 support dropped.

### 2013-05-02

* [BC BREAK] New argument in method \Exporter\Writer\SitemapWriter::generateSitemapIndex
             to handle absolute URL.
