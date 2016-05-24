# MinkExtension

[![Build
Status](https://secure.travis-ci.org/Behat/MinkExtension.png?branch=master)](http://travis-ci.org/Behat/MinkExtension)

Provides integration layer:

* Additional services for Behat (`Mink`, `Sessions`, `Drivers`).
* `Behat\MinkExtension\Context\MinkAwareInterface` which provides `Mink`
  instance for your contexts or subcontexts.
* Base `Behat\MinkExtension\Context\MinkContext` context which provides base
  step definitions and hooks for your contexts or subcontexts. Or it could be
  even used as subcontext on its own.

between Behat 2.4+ and Mink 1.4+.

## Docs

[Official documentation](http://extensions.behat.org/mink/index.html).

## Translated languages

For now exist 10 translated languages: `cs`,`de`,`es`,`fr`,`ja`,`nl`,`pl`,`pt`,`ru`,`sv`.

**Note:** The `ja`,`nl` and `sv` are outdated.

#### How to add a new translated language?

If you want to translate another language, you can use as reference the `ru` language file under
[translations folder](https://github.com/Behat/MinkExtension/tree/master/i18n).

**Important:** The filename must match with the same translated language name in [Behat](https://github.com/Behat/Behat/tree/master/i18n) and [Gherkin](https://github.com/Behat/Gherkin/blob/master/i18n.php) in order to work correctly.

If the language does not exist in [Gherkin](https://github.com/Behat/Gherkin/tree/master/i18n).
You should consider making a [Pull Request](https://github.com/cucumber/cucumber/pulls) to
[cucumber\gherkin i18n file](https://github.com/cucumber/gherkin/blob/master/lib/gherkin/i18n.yml).

## Copyright

Copyright (c) 2012 Konstantin Kudryashov (ever.zet). See LICENSE for details.

## Contributors

* Konstantin Kudryashov [everzet](http://github.com/everzet) [lead developer]
* Other [awesome developers](https://github.com/Behat/MinkExtension/graphs/contributors)

## Sponsors

* knpLabs [knpLabs](http://www.knplabs.com/) [main sponsor]
