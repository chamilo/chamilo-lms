CHANGELOG
=========

0.4.0
-----

* dropped suppport for PHP < 5.6 and HHVM

* made the package compatible with `3.x` releases of `ramsey/uuid`

* allow `2.x` and `3.x` releases of the `php-xapi/model` package too

* added an `ActivityRepositoryInterface` that defines the public API of
  an activity repository

0.3.1
-----

* allow `3.x` releases of `ramsey/uuid`
* fix compatibility with PHPUnit 6+

0.3.0
-----

* Removed the `MappedStatement` and `MappedVerb` classes. They are needed
  for Doctrine based implementations and thus have been moved to the
  `php-xapi/repository-doctrine` package. Consequently, the `StatementRepository`
  class has been removed too. You now have to implement the `StatementRepositoryInterface`
  and handle `Statement` classes directly instead.

* Removed the `NotFoundException` in favor of the exception with the same
  name from the `php-xapi/exception` package.

* The public API now uses `StatementId` instances instead of strings to carry
  information about statement ids. This means changes to the following methods:

  * `StatementRepositoryInterface::findStatementById()`: The `$statementId`
    argument is now type hinted with `StatementId`.

  * `StatementRepositoryInterface::findVoidedStatementById()`: The `$voidedStatementId`
    argument is now type hinted with `StatementId`.

  * `StatementRepositoryInterface::storeStatement()`: The method returns a
    `StatementId` instance instead of a string.

* Added a `StatementRepositoryInterface` that defines the public API of a
  statement repository. You can still extend the base `StatementRepository`
  class or provide your own implementation of this new interface.

* The requirements for `php-xapi/model` and `php-xapi/test-fixtures` have
  been bumped to `^1.0` to make use of their stable releases.

0.2.0
-----

* changed base namespace of all classes from `Xabbuh\XApi\Storage\Api` to
  `XApi\Repository\Api`

0.1.2
-----

Do not allow to pull in packages that could potentially break backwards
compatibility.

0.1.1
-----

Moved `php-xapi/test-fixtures` package to the `require` section as the package
is required by other packages that make use of the base test class.

0.1.0
-----

First release defining a common interface for LRS repository backends.

This package replaces the `xabbuh/xapi-storage-api` package which is now
deprecated and should no longer be used.
