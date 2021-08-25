UPGRADE
=======

Upgrading from 0.3 to 0.4
-------------------------

* The `XApi\Repository\Doctrine\Mapping\Object` class was renamed to
  `XApi\Repository\Doctrine\Mapping\StatementObject` for compatibility with
  PHP 7.2.

Upgrading from 0.2 to 0.3
-------------------------

* The `MappedStatement` and `MappedVerb` classes have been removed from the
  `php-xapi/model` package. They have been replaced with the new `Statement`
  and `Verb` classes in the `XApi\Repository\Doctrine\Mapping` namespace of
  this package. Consequently, the `MappedStatementRepository` class has been
  removed. It was replaced with a new `StatementRepository` class in the
  `XApi\Repository\Doctrine\Repository\Mapping` namespace.

* The requirements for `php-xapi/model` and `php-xapi/test-fixtures` have
  been bumped to `^1.0` to make use of their stable releases.

* The required version of the `php-xapi/repository-api` package has been
  raised to `^0.3`.

Upgrading from 0.1 to 0.2
-------------------------

* Moved base functional `StatementRepositoryTest` test case class to the
  `XApi\Repository\Doctrine\Test\Functional` namespace.

* The base namespace was changed from `Xabbuh\XApi\Storage\Doctrine` to
  `XApi\Repository\Doctrine`.
