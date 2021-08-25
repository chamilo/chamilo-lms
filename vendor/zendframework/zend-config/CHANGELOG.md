# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.3.0 - 2019-06-08

### Added

- [#54](https://github.com/zendframework/zend-config/pull/54) adds support for PHP 7.3.
- [#58](https://github.com/zendframework/zend-config/pull/58) adds
  `$processSections` to INI reader, allowing control over whether sections
  should be parsed or not
- [#63](https://github.com/zendframework/zend-config/pull/63) adds .yml to
  Zend\Config\Factory as an alternative extension for yaml

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.0 - 2018-04-24

### Added

- [#47](https://github.com/zendframework/zend-config/pull/47) adds `Zend\Config\Writer\JavaProperties`, a complement to
  `Zend\Config\Reader\JavaProperties`, for writing JavaProperties files from configuration. The writer supports
  specifying an alternate key/value delimiter (the default is ":") via the constructor.

- [#46](https://github.com/zendframework/zend-config/pull/46) adds a constructor option to the JavaProperties reader to allow
  users to indicate keys and values from the configuration should be trimmed of whitespace:

  ```php
  $reader = new JavaProperties(
    JavaProperties::DELIMITER_DEFAULT, // or ":"
    JavaProperties::WHITESPACE_TRIM,   // or true; default is false
  );
  ```

- [#45](https://github.com/zendframework/zend-config/pull/45) adds the ability to specify an alternate key/value delimiter to
  the JavaProperties config reader via the constructor: `$reader = new JavaProperties("=");`.

- [#42](https://github.com/zendframework/zend-config/pull/42) adds support for PHP 7.1 and 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#42](https://github.com/zendframework/zend-config/pull/42) removes support for HHVM.

### Fixed

- Nothing.

## 3.1.0 - 2017-02-22

### Added

- [#37](https://github.com/zendframework/zend-config/pull/37) adds a new method,
  `enableKeyProcessing()`, and constructor argument, `$enableKeyProcessing =
  false`,  to each of the `Token` and `Constant` processors. These allow enabling
  processing of tokens and/or constants encountered in configuration key values.

- [#37](https://github.com/zendframework/zend-config/pull/37) adds the ability
  for the `Constant` processor to process class constants, including the
  `::class` pseudo-constant.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.0 - 2017-02-16

### Added

- [#36](https://github.com/zendframework/zend-config/pull/36) adds support for
  [PSR-11](http://www.php-fig.org/psr/psr-11/).

- [#36](https://github.com/zendframework/zend-config/pull/36) adds the class
  `Zend\Config\StandaloneReaderPluginManager` for managing config reader plugins.
  This implementation implements the PSR-11 `ContainerInterface`, and uses a
  hard-coded list of reader plugins.

- [#36](https://github.com/zendframework/zend-config/pull/36) adds the class
  `Zend\Config\StandaloneWriterPluginManager` for managing config writer plugins.
  This implementation implements the PSR-11 `ContainerInterface`, and uses a
  hard-coded list of writer plugins.

### Changes

- [#36](https://github.com/zendframework/zend-config/pull/36) updates the
  `Zend\Config\Factory::getReaderPluginManager()` method to lazy-load a
  `StandaloneReaderPluginManager` by default, instead of a
  `ReaderPluginManager`, allowing usage out-of-the-box without requiring
  zend-servicemanager.

- [#36](https://github.com/zendframework/zend-config/pull/36) updates the
  `Zend\Config\Factory::setReaderPluginManager()` method to typehint against
  `Psr\Container\ContainerInterface` instead of `ReaderPluginManager`. If you
  were extending and overriding that method, you will need to update your
  signature.

- [#36](https://github.com/zendframework/zend-config/pull/36) updates the
  `Zend\Config\Factory::getWriterPluginManager()` method to lazy-load a
  `StandaloneWriterPluginManager` by default, instead of a
  `WriterPluginManager`, allowing usage out-of-the-box without requiring
  zend-servicemanager.

- [#36](https://github.com/zendframework/zend-config/pull/36) updates the
  `Zend\Config\Factory::setWriterPluginManager()` method to typehint against
  `Psr\Container\ContainerInterface` instead of `WriterPluginManager`. If you
  were extending and overriding that method, you will need to update your
  signature.

### Deprecated

- Nothing.

### Removed

- [#36](https://github.com/zendframework/zend-config/pull/36) removes usage of
  zend-json as a JSON de/serializer in the JSON writer and reader; the
  component now requires ext/json is installed to use these features.

### Fixed

- Nothing.

## 2.6.0 - 2016-02-04

### Added

- [#6](https://github.com/zendframework/zend-config/pull/6) adds the ability for
  the `PhpArray` writer to optionally translate strings that evaluate to known
  classes to `ClassName::class` syntax; the feature works for both keys and
  values.
- [#21](https://github.com/zendframework/zend-config/pull/21) adds revised
  documentation, and publishes it to https://zendframework.github.io/zend-config/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#8](https://github.com/zendframework/zend-config/pull/8),
  [#18](https://github.com/zendframework/zend-config/pull/18), and
  [#20](https://github.com/zendframework/zend-config/pull/20) update the
  code base to make it forwards-compatible with the v3.0 versions of
  zend-stdlib and zend-servicemanager. Primarily, this involved:
  - Updating the `AbstractConfigFactory` to implement the new methods in the
    v3 `AbstractFactoryInterface` definition, and updating the v2 methods to
    proxy to those.
  - Updating `ReaderPluginManager` and `WriterPluginManager` to follow the
    changes to `AbstractPluginManager`. In particular, instead of defining
    invokables, they now define a combination of aliases and factories (using
    the new `InvokableFactory`); additionally, they each now implement both
    `validatePlugin()` from v2 and `validate()` from v3.
  - Pinning to stable versions of already updated components.
  - Selectively omitting zend-i18n-reliant tests when testing against
    zend-servicemanager v3.
