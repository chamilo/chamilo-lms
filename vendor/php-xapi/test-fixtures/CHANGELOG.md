CHANGELOG
=========

1.1.0
-----

* made the package compatible with `3.x` releases of `ramsey/uuid`
* allow `2.x` releases of the `php-xapi/model` package too
* allow `3.x` releases of the `php-xapi/model` package too

1.0.1
-----

Raw content data has been added to the "text attachment" and "JSON attachment"
fixtures.

1.0.0
-----

This first stable release contains all the official conformance test fixtures
provided by the maintainers of the xAPI spec as instances of the class offered
by the `php-xapi/model`  package and provides ways to access them through
a PHP API.

This release is equivalent to the 0.5 release series (except for the version
of the required `php-xapi/model` package being 1.x now) and is considered
to be stable.

0.5.0
-----

* Updated fixtures to use `IRI` and `IRL` instances where they have been
  introduced in the `php-xapi/model` package.

* Added a `Statement` fixture defining all properties of a statement.

* Added fixtures for statement attachments.

* Added fixtures for activity definition extensions.

* Added fixtures for activity interaction definitions and interaction components.

* Updated fixtures for `Definition`, `Statement`, `StatementResult`, and
  `Verb` to use `LanguageMap` instances instead of plain old PHP arrays.

* Updated fixtures for `Statement`, `StatementReference`, and `StatementResult`
  to use `StatementId` objects instead of string ids.

* Added missing fixtures for `Account`, `Activity`, `Actor`, `Context`,
  `ContextActivities`, `Extensions`, `Result`, and `Verb`.

0.4.0
-----

* Added missing `timestamp` value to the typical statement.

* when `null` is passed as the id argument to one of the methods of the
  `StatementFixtures` class, a unique UUID will be generated

0.3.0
-----

Switched to make use of the official conformance test fixtures that are provided
by the maintainers of the xAPI spec.

0.2.0
-----

* compatibility with release 0.2 of the `php-xapi/model` package

0.1.1
-----

* restrict dependency version to not pull in potentially BC breaking package
  versions

0.1.0
-----

This is the first release containing test fixtures for actors (agents and
groups), verbs, documents, activities,statements, and statement results.

This package replaces the `xabbuh/xapi-data-fixtures` package which is now
deprecated and should no longer be used.
