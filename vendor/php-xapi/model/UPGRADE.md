UPGRADE
=======

Upgrading from 2.x to 3.0
-------------------------

* The `Object` class has been renamed to `StatementObject`.

Upgrading from 1.x to 2.0
-------------------------

* The `StatementsFilter::format()`, `StatementsFilter::includeAttachments()`,
  and `StatementsFilter::excludeAttachments()` methods have been removed.

Upgrading from 1.0 to 1.1
-------------------------

* The `StatementsFilter::format()`, `StatementsFilter::includeAttachments()`,
  and `StatementsFilter::excludeAttachments()` methods are deprecated and will
  be removed in 2.0.

* Constructing an `Attachment` instance with specifying neither a file URL
  nor the raw attachment content throws an `\InvalidArgumentException`.

Upgrading from 0.4 to 0.5
-------------------------

* The type of the following properties has been changed from `string` to
  instances of the new `IRI` class:

  * `Activity::$id`
  * `Attachment::$usageType`
  * `Definition::$type`
  * `InverseFunctionalIdentifier::$mbox`
  * `Verb::$id`

  Type hints of respective methods have been updated accordingly.

* The type of the following properties has been changed from `string` to
  instances of the new `IRL` class:

  * `Account::$homePage`
  * `Attachment::$fileUrl`
  * `Definition::$moreInfo`
  * `StatementResult::$moreUrlPath`

  Type hints of respective methods have been updated accordingly.

* The `$display` property of the `Verb` class as well as the `$name` and
  `$description` properties of the `Definition` class are no longer plain
  PHP arrays, but are now instances of `LanguageMap`.

* Statement ids are no longer plain strings, but are `StatementId` value objects:

  Before:

  ```php
  // passing an id to the Statement constructor
  $statement = new Statement('16fd2706-8baf-433b-82eb-8c7fada847da', ...);

  // building a new statement based on an existing one with a different id
  $statement = $statement->withId('39e24cc4-69af-4b01-a824-1fdc6ea8a3af');

  // reference another statement with its id
  $statementRef = new StatementReference('16fd2706-8baf-433b-82eb-8c7fada847da');
  ```

  After:

  ```php
  // passing an id to the Statement constructor
  $statement = new Statement(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'), ...);

  // building a new statement based on an existing one with a different id
  $statement = $statement->withId(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'));

  // reference another statement with its id
  $statementRef = new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));
  ```

* The constructor of the `SubStatement` class now throws an exception when
  a `SubStatement` instance is passed as the `$object` argument to comply
  with the Experience API spec which does not allow to nest sub statements.

* The `$id` attribute has been removed from the `SubStatement` class. Also,
  the `$id` argument of the class constructor has been removed respectively.
  The first constructor argument is now the sub statement's actor.

* The `getStatementReference()` and `getVoidStatement()` methods have been
  removed from the `SubStatement` class as they are not usable without an id.

Upgrading from 0.3 to 0.4
-------------------------

* The argument type of the `equals()` method in the `Actor` base class was
  changed from `Actor` to `Object` to be compatible with the same method from
  the parent `Object` class.

Upgrading from 0.2 to 0.3
-------------------------

* The default value of the `display` property of the `Verb` class was changed
  to `null` (was the empty array before).

Upgrading from 0.2.0 to 0.2.1
-----------------------------

* Data passed to the `Score` class during construction is no longer cast to
  `float` values to ensure that integers are not needlessly cast. You need to
  make sure to always pass the expected data types when build `Score` objects.

Upgrading from 0.1 to 0.2
-------------------------

* the getter methods to retrieve the inverse functional identifier properties
  `mbox`, `mboxsha1sum`, `openid`, and `account` have been removed from the
  `Actor` class

* the `getInverseFunctionalIdentifier()` method in the `Actor` class no longer
  returns a string, but returns an `InverseFunctionalIdentifier` instance
  instead

* A new class `InverseFunctionalIdentifier` was introduced to reflect the
  inverse functional identifier of an actor. It reflects the fact that an IRI
  must only contain exactly one property of `mbox`, `mboxsha1sum`, `openid`,
  and `account` by providing four factory methods to obtain an IRI instance:

  * `withMbox()`

  * `withMboxSha1Sum()`

  * `withOpenId()`

  * `withAccount()`

  You now need to pass an `InverseFunctionalIdentifier` when creating an actor
  or group.

  Before:

  ```php
  use Xabbuh\XApi\Model\Agent;
  use Xabbuh\XApi\Model\Group;

  $agent = new Agent(
      'mailto:christian@example.com',
      null,
      null,
      null,
      'Christian'
  );
  $group = new Group(
      null,
      null,
      null,
      new Account('GroupAccount', 'http://example.com/homePage'),
      'Example Group'
  );
  ```

  After:

  ```php
  use Xabbuh\XApi\Model\Agent;
  use Xabbuh\XApi\Model\Group;
  use Xabbuh\XApi\Model\InverseFunctionalIdentifier;

  $agent = new Agent(
      InverseFunctionalIdentifier::withMbox('mailto:christian@example.com'),
      'Christian'
  );
  $group = new Group(
      InverseFunctionalIdentifier::withAccount(
          new Account('GroupAccount', 'http://example.com/homePage')
      ),
      'Example Group'
  );
  ```

* The `Statement` class is now marked as final. This means that you can no
  longer extend it.
