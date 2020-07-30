1.4.0 / 2020-03-11
==================

Bug fixes:

* Changed phpdoc types from `Boolean` to `boolean` to be compatible with psalm type checking
* Don't use TABs, when triggering `change` JS event upon input value is change (fixes some auto-complete control testing in Google Chrome)
* Fixed inability to manipulate windows when Selenium 3 with Firefox GeckoDriver was used
* The `clickOnElement` method wasn't working when Selenium 3 with Firefox GeckoDriver was used
* Fixed the handling of cookies on PHP 7.4

Changes:

* Bumped requirement to PHP 5.4

New features:

* Allow uploading files to remote Selenium instances (e.g. SauceLabs, BrowserStack, etc.)
* Added `getDesiredCapabilities` method for fetching current desired capabilities
* Added support for `goog:chromeOptions` to specify custom Chrome options, which is the name used by newer ChromeDriver releases

Testsuite:

* Don't test on PHP 5.3 (driver itself would likely continue to work on PHP 5.3 for some time)
* Adding testing on PHP 7.1, 7.2, 7.3 and 7.4
* Removed PhantomJS

Misc:

* Syn library is [0.0.3](https://github.com/bitovi/syn/tree/v0.0.3)
* The `setDesiredCapabilities` method combines default capabilities with user provided ones
* Removed outdated default capabilities
* The `setDesiredCapabilities` method will throw an exception, when used on a started session

1.3.1 / 2016-03-05
==================

Bug fixes:

* Fixed the handling of cookies with semicolon in the value

Testsuite:

* Add testing on PHP 7

1.3.0 / 2015-09-21
==================

New features:

* Updated the driver to use findElementsXpaths for Mink 1.7 and forward compatibility with Mink 2

Testsuite:

* Fixed the window name test for the chrome driver
* Add testing on PhantomJS 2

Misc:

* Updated the repository structure to PSR-4

1.2.0 / 2014-09-29
==================

BC break:

* Changed the behavior of `getValue` for checkboxes according to the BC break in Mink 1.6

New features:

* Added the support of the `chromeOptions` argument in capabilities
* Added the support of select elements in `setValue`
* Added the support of checbox and radio elements in `setValue`
* Added the support of HTML5 input types in `setValue` (for those supported by WebDriver itself)
* Added `getWebDriverSessionId` to get the WebDriver session id
* Added a way to configure the webdriver timeouts
* Implemented `getOuterHtml`
* Implemented `getWindowNames` and `getWindowName`
* Implemented `maximizeWindow`
* Implemented `submitForm`
* Implemented `isSelected`

Bug fixes:

* Fixed the selection of options for radio groups
* Fixed `getValue` for radio groups
* Fixed the selection of options for multiple selects to ensure the change event is triggered only once
* Fixed mouse interactions to use the webDriver API rather than using JS and emulating events
* Fixed duplicate change events being triggered when setting the value
* Fixed the code to throw exceptions for invalid usages of the driver
* Fixed the implementation of `mouseOver`
* Fixed `evaluateScript` and `executeScript` to support all syntaxes required by the Mink API
* Fixed the retrieval of HTML attributes in `getAttribute`
* Fixed form interactions to use the webDriver API rather than using JS and emulating change events
* Fixed the clearing of the value when the caret is at the beginning of the field in `setValue`

Testing:

* Updated the testsuite to use the new Mink 1.6 driver testsuite
* Added testing on HHVM
