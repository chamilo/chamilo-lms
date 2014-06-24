# CHANGELOG

### 2.3.2 (2014-06-17)

 * 88b21ea - [Travis] Add Symfony 2.5 + Remove 2.0 branch
 * 5793ab2 - [Helper] Fix test according to Symfony
 * ea39ae1 - [DependencyInjection] Prepend resources instead of append them
 * 7d6f016 - [Test] Move fixtures at the root test directory
 * 805161b - [Model] Improve interface PHPDoc
 * 8e1cb8d - [Test] Fix PHPDoc
 * e5aeb4c - [README] Add Scrutunizer CI badge
 * ccfd632 - [Helper] Refactor CKEditorHelper::renderReplace for better comprehension
 * fb0c5e5 - Fix PHPDoc + CS
 * 1ef08af - [DependencyInjection] Refactor extension for a better comprehension
 * d8cef6f - [DependencyInjection] Split configuration
 * 6c10e68 - [DependencyInjection] Rely on ConfigurableExtension

### 2.3.1 (2014-05-26)

 * 478c4ed - Fix CKEditor target branch
 * b430689 - Upgrade CKEditor to 4.4.1

### 2.3.0 (2014-05-16)

 * 4fb29d1 - [Helper] Only load the CKEditor library one time
 * 41636f9 - Add coveralls support
 * 13e7038 - Allow RegExp by relying on egeloen/json-builder
 * ac6db2a - Upgrade CKEditor to 4.4.0
 * 648aa63 - [Helper] Only render StylesSet if they are not already registered

### 2.2.1 (2014-01-31)

 * aa81171 - [Travis] Make symfony/form dynamic
 * e51427c - [Twig] Fix js escaping
 * fa08cd3 - [Twig] Fix caching by lazy loading services scoped request

### 2.2.0 (2014-01-30)

 * db93af5 - [Model] Move all view logic to an helper
 * ff12310 - Upgrade CKEditor to 4.3.2
 * 2b1786a - Update new year
 * cdad813 - Deprecate Symfony 2.0

### 1.1.9 - 2.1.9 (2014-01-04)

 * ebeb553 - [ConfigManager] Fix merge config behavior
 * 4808b41 - Fix Config, Plugin, Template & StylesSet arrays initialization
 * ec2f56d - [Template] Fix textarea value escaping
 * 58f9549 - [ConfigManager] Allow to define filebrowser*Url via a closure
 * 9ecc2c1 - [Model] Add stylesSet support
 * c199353 - [Model] Add templates support

### 1.1.8 - 2.1.8 (2013-12-12)

 * b22d11c - Upgrade CKEditor to 4.3.1
 * c9e65d7 - [Travis] Simplify matrix + Add Symfony 2.4 to the build
 * 434e92f - [Type] Add CKEditor constants support
 * af8f9da - Upgrade CKEditor to 4.3

### 1.1.7 - 2.1.7 (2013-10-09)

 * 03f90cf - Upgrade CKEditor to 4.2.1
 * 4a37ad8 - [Doc] SonataMedia integration
 * a94df4f - [DependencyInjection] Introduce built-in toolbars (basic, standard, full)
 * bcae378 - [Doc] Fix FMElfinderBundle integration example

### 1.1.6 - 2.1.6 (2013-08-22)

 * 992c7df - [Form] Simplify default configuration handling
 * 8c085cc - [Doc] Add FMElfinderBundle documentation
 * a8a9a7e - [Form] Allow required html attribute

### 1.1.5 - 2.1.5 (2013-07-18)

 * 3bfbc01 - Upgrade CKEditor to 4.2
 * 099bf82 - [Composer] Add branch alias
 * c04e2ed - [Twig] Don't escape textarea value
 * ecef869 - Add default configuration support

### 1.1.4 - 2.1.4 (2013-06-17)

 * 43d2675 - Upgrade CKEditor to 4.1.1
 * cb9598b - [Travis] Use --prefer-source to avoid random build fail
 * 4da8e71 - PSR2 compatibility
 * 133ef7b - [Composer] Add PHPUnit in require-dev & use it in travis

### 1.1.3 - 2.1.3 (2013-03-18)

 * 464fd64 - Add PHP templating engine support
 * eb7c407 - Remove trim asset version twig extension & use the service instead
 * 3634c65 - Allow to use custom CKEditor versions
 * cca336a - Extract assets version trim logic in a dedicated service
 * 2093bcb - [Type] Allow to disable CKEditor at the widget level
 * c1a89c3 - [PluginManager] Refactor to handle assets support
 * 4250a92 - [ConfigManager] Fix contentsCss if the application does not live at the root of the host
 * a2384c7 - Fix CKEditor destruction when it is loaded multiple times by AJAX (Sonata compatibility)
 * de8073f - Upgrade CKEditor to 4.0.2
 * 861d418 - Allow to disable ckeditor widget for testing purpose
 * ec29bfb - [Build] Add bash script to sync stable CKEditor release

### 1.1.2 - 2.1.2 (2013-02-19)

 * a6b1556 - Add plugins support
 * c796be2 - Normalize line endings
 * d078d28 - Handle filebrowser URL generation

### 1.1.1 - 2.1.1 (2013-01-27)

 * e0b086a - Allow to configure ckeditor form type through configuration
 * 038d7c1 - Upgrade CKEditor to 4.0.1
 * b90ea78 - Fix assets_version support
 * 4be2e56 - Add support for assets_version
 * e787087 - [Widget] Remove autoescape js

### 1.1.0 - 2.1.0 (2013-01-12)

 * fd79848 - [Form][Type] Allow to set all config options.

### 1.0.0 - 2.0.0 (2013-01-12)
