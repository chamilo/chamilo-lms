# MinkExtension

[![Build
Status](https://travis-ci.org/Behat/MinkExtension.svg?branch=master)](https://travis-ci.org/Behat/MinkExtension)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Behat/MinkExtension/badges/quality-score.png?s=c6474ca52322f5176a2f0cab10974aeee5e6133c)](https://scrutinizer-ci.com/g/Behat/MinkExtension/)

MinkExtension is an integration layer between Behat 3.0+ and Mink 1.5+
and it provides:

* Additional services for Behat (``Mink``, ``Sessions``, ``Drivers``).
* ``Behat\MinkExtension\Context\MinkAwareContext`` which provides ``Mink``
  instance for your contexts.
* Base ``Behat\MinkExtension\Context\MinkContext`` context which provides base
  step definitions and hooks for your contexts or subcontexts. Or it could be
  even used as context on its own.

## Docs

[Official documentation](doc/index.rst).

## Translated languages

For now exist 11 translated languages: `cs`,`de`,`es`,`fr`,`ja`,`nl`,`pl`,`pt`,`ro`,`ru`,`sv`.

**Note:** The `ja`,`nl` and `sv` are outdated.

#### How to add a new translated language?

If you want to translate another language, you can use as reference the `ru` language file under
[translations folder](https://github.com/Behat/MinkExtension/tree/master/i18n).

**Important:** The filename must match with the same translated language name in [Behat](https://github.com/Behat/Behat/blob/master/i18n.php) and [Gherkin](https://github.com/Behat/Gherkin/blob/master/i18n.php) in order to work correctly.

If the language does not exist in [Gherkin](https://github.com/Behat/Gherkin/blob/master/i18n.php).
You should consider [contributing to Gherkin translations](https://github.com/Behat/Gherkin/blob/master/CONTRIBUTING.md#contributing-to-gherkin-translations).

## Copyright

Copyright (c) 2012 Konstantin Kudryashov (ever.zet). See LICENSE for details.

## Contributors

* Konstantin Kudryashov [everzet](http://github.com/everzet) [lead developer]
* Other [awesome developers](https://github.com/Behat/MinkExtension/graphs/contributors)
