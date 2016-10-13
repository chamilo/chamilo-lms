# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.2.0](https://github.com/sonata-project/SonataBlockBundle/compare/3.1.1...3.2.0) - 2016-09-20
### Added
- Created `Sonata\BlockBundle\Block\Service\AbstractAdminBlockService` class
- Created `Sonata\BlockBundle\Block\Service\AbstractBlockService` class
- Created `Sonata\BlockBundle\Block\Service\AdminBlockServiceInterface` class
- Created `Sonata\BlockBundle\Block\Service\BlockServiceInterface` class

### Deprecated
- The class `Sonata\BlockBundle\Block\AbstractBlockService` is deprecated
- The class `Sonata\BlockBundle\Block\BaseBlockService` is deprecated
- The class `Sonata\BlockBundle\Block\BlockAdminServiceInterface` is deprecated
- The class `Sonata\BlockBundle\Block\BlockServiceInterface` is deprecated

## [3.1.1](https://github.com/sonata-project/SonataBlockBundle/compare/3.1.0...3.1.1) - 2016-07-12
### Deprecated
- Deprecate `Tests\Block\Service\FakeTemplating` in favor of `Test\Mock\MockTemplating` (missing PR for 3.1.0)

## [3.1.0](https://github.com/sonata-project/SonataBlockBundle/compare/3.0.1...3.1.0) - 2016-07-12
### Changed
- Tests for `*BlockService*` now uses `AbstractBlockServiceTestCase`

### Deprecated
- Deprecate empty class `BaseTestBlockService`
- Deprecate `Tests\Block\AbstractBlockServiceTest` in favor of `Test\AbstractBlockServiceTestCase`

### Fixed
- Profiler block design for Symfony Profiler v2

### Removed
- Internal test classes are now excluded from the auto-loader

## [3.0.1](https://github.com/sonata-project/SonataBlockBundle/compare/3.0.0...3.0.1) - 2016-06-14
### Changed
- The log level on exceptions in `BlockRenderer` is decreased from critical to error
- Replaced profiler icon with existing icon from profiler toolbar

### Fixed
- Error with the default extension configuration for `config:dump-reference` command

### Removed
- Removed the asterisk sign from the profiler toolbar to be compliant with Symfony standard
