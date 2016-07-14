# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.5.0](https://github.com/sonata-project/exporter/compare/1.4.1...1.5.0) - 2016-06-16
### Added
- `MimeTypedWriterInterface` can be implemented to indicate the suitable `Content-Type` header and format for a writer.

### Changed
- Rename `lib` folder to `src` and make this project PSR-4 compliant.

## [1.4.0](https://github.com/sonata-project/exporter/compare/1.3.4...1.4.0) - 2015-06-09
### Added
- Add possibility to set custom tag names on `XmlSourceIterator`

### Changed
- Replaced deprecated `PropertyAccess::getPropertyAccessor()` method `PropertyAccess::createPropertyAccessor()`.

### Removed
- Symfony 2.2 support dropped.

## [1.2.2](https://github.com/sonata-project/exporter/compare/1.2.1...1.2.2) - 2013-05-02
### Added
- Add new argument in method \Exporter\Writer\SitemapWriter::generateSitemapIndex to handle absolute URL.
