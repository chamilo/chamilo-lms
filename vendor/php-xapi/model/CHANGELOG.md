CHANGELOG
=========

3.2.0
-----

* Added support for PHP 8.

3.1.0
-----

* Introduce a new `Person` class.
* Constructing a `State` object with an instance of any child class of `Actor`
  other than `Agent` as the second argument is deprecated. Starting with `4.0`,
  only instances of `Agent` will be accepted.
* The `State::getActor()` method is deprecated and will be removed in `4.0`.
  Use `State::getAgent()` instead.
* Added a `StateDocumentsFilter` class that allows to draft filters for
  `StateDocument` objects.

3.0.0
-----

* The `Statement::getTimestamp()` method has been removed. Use
  `Statement::getCreated()` instead.
* The `Statement::withTimestamp()` method has been removed. Use
  `Statement::withCreated()` instead.
* The `SubStatement::getTimestamp()` method has been removed. Use
  `SubStatement::getCreated()` instead.
* The `SubStatement::withTimestamp()` method has been removed. Use
  `SubStatement::withCreated()` instead.
* Dropped support for PHP < 7.1 as well as HHVM.
* The `Object` class was renamed to `StatementObject` for compatibility with PHP
  7.2.

2.2.0
-----

* Introduce a new `Person` class.
* Constructing a `State` object with an instance of any child class of `Actor`
  other than `Agent` as the second argument is deprecated. Starting with `4.0`,
  only instances of `Agent` will be accepted.
* The `State::getActor()` method is deprecated and will be removed in `4.0`.
  Use `State::getAgent()` instead.
* Added a `StateDocumentsFilter` class that allows to draft filters for
  `StateDocument` objects.

2.1.0
-----

* The `Statement::getTimestamp()` method is deprecated and will be removed in
  3.0. Use `Statement::getCreated()` instead.
* The `Statement::withTimestamp()` method is deprecated and will be removed in
  3.0. Use `Statement::withCreated()` instead.
* The `SubStatement::getTimestamp()` method is deprecated and will be removed in
  3.0. Use `SubStatement::getCreated()` instead.
* The `SubStatement::withTimestamp()` method is deprecated and will be removed in
  3.0. Use `SubStatement::withCreated()` instead.

2.0.1
-----

* Added missing `$version` attribute to the `Statement` class which defaults to
  `null`.

2.0.0
-----

* Introducing a new `Uuid` class that supports both `ramsey/uuid` 2.x and 3.x.
  Applications and packages using this library should not longer refer to the
  `Uuid` class from the `ramsey/uuid` package, but should always use the `Uuid`
  class from the this package instead.
* The type of the `StatemendId::$id` property has been changed from an instance
  of `Rhumsaa\Uuid\Uuid` to an instance of the added `Uuid` class.

1.2.0
-----

* The `Statement::getTimestamp()` method is deprecated and will be removed in
  3.0. Use `Statement::getCreated()` instead.
* The `Statement::withTimestamp()` method is deprecated and will be removed in
  3.0. Use `Statement::withCreated()` instead.
* The `SubStatement::getTimestamp()` method is deprecated and will be removed in
  3.0. Use `SubStatement::getCreated()` instead.
* The `SubStatement::withTimestamp()` method is deprecated and will be removed in
  3.0. Use `SubStatement::withCreated()` instead.
* Introduce a new `Person` class.
* Constructing a `State` object with an instance of any child class of `Actor`
  other than `Agent` as the second argument is deprecated. Starting with `4.0`,
  only instances of `Agent` will be accepted.
* The `State::getActor()` method is deprecated and will be removed in `4.0`.
  Use `State::getAgent()` instead.
* Added a `StateDocumentsFilter` class that allows to draft filters for
  `StateDocument` objects.

1.1.1
-----

* Added missing `$version` attribute to the `Statement` class which defaults to
  `null`.

1.1.0
-----

* The `StatementsFilter::format()`, `StatementsFilter::includeAttachments()`,
  and `StatementsFilter::excludeAttachments()` methods are deprecated and will
  be removed in 2.0.

* Added a `$content` attribute to the `Attachment` class to make it possible
  to attach the raw content to an attachment.

1.0.1
-----

* Added missing `$timestamp` and `$attachments` properties to the `SubStatement`
  class.

* Fixed comparing scores where some values are integers instead of floats.

1.0.0
-----

This is the first stable release of the 1.x series. All objects that are
part of the 1.0.1 Experience API spec have been implemented and the current
API is considered to be stable.

In terms of existing classes and methods, there is no difference to the
0.5 release series.

0.5.1
-----

* Fixed the handling of `IRI` instances (for `Verb::$id` and `Activity::$id`)
  in `StatementsFilter` to only include the IRI's string value in the generated
  filter.

0.5.0
-----

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

* Fixed how context attributes of statements are dealt with in `Statement::equals()`
  (previously, they were completely ignored).

* Fixed handling `null` values for statement ids inside `Statement::equals()`.
  Previously, `StatementId::equals()` might have been called even if a statement's
  identifier was not defined.

* Added an `Attachment` class to store statement attachments.

* Fixed some edge cases in `Context::equals()` where statement references,
  teams, and extensions would not have been compared properly.

* Added missing extensions to activity definitions.

* Added `with*()` methods for the `$name`, `$description`, `$type`, and `$moreInfo`
  properties of the `Definition` class and its subclasses to ease the creation
  of new `Definition` objects based on existing instances.

* Added new classes to model user interaction activity definitions (all extending
  an abstract `InteractionDefinition` class which in turn is a child class
  of the already existing `Definition` class):

   * `ChoiceInteractionDefinition`
   * `FillInInteractionDefinition`
   * `LikertInteractionDefinition`
   * `LongFillInInteractionDefinition`
   * `MatchingInteractionDefinition`
   * `NumericInteractionDefinition`
   * `OtherInteractionDefinition`
   * `PerformanceInteractionDefinition`
   * `SequencingInteractionDefinition`
   * `TrueFalseInteractionDefinition`

  Interaction components which are part of some of the new definition classes
  will be handled by `InteractionComponent` instances.

* Added a `LanguageMap` value object class to model the `$display` property
  of `Verb` instances as well as the `$name` and `$description` properties
  of the `Definition` class (all these properties have been plain PHP arrays
  before).

* Added a value object for statement ids.

* The constructor of the `SubStatement` class now throws an exception when
  a `SubStatement` instance is passed as the `$object` argument to comply
  with the Experience API spec which does not allow to nest sub statements.

* Removed the `$id` property from the `SubStatement` class as well as the
  `getStatementReference()` and `getVoidStatement()` methods which relied
  on the existence of an id as a sub statement must not have an id according
  to the xAPI spec.

* added a `$context` attribute to `SubStatement` instances

* added `equals()` method to the `Result` model class

* added a `StatementFactory` to ease the creation of complex xAPI statements

* Added `with*()` methods to `Result`, `Score`, `Statement`, `SubStatement`
  that allow the creation of new model objects based on existing instances.

* added `Context`, `ContextActivities`, and `Extensions` classes that represent
  statement contexts, their context's activities, and statement extensions
  respectively

0.4.1
-----

* also compare timestamps when performing statement equality checks

0.4.0
-----

* fixed some edge cases in the `equals()` methods of the `Definition`, `Result`,
  and `Verb` classes

* Made `Object` a parent class of the `Actor` class to reflect the fact that
  actors can also be objects in xAPI statements.

* The argument type of the `equals()` method in the `Actor` base class was
  changed from `Actor` to `Object` to be compatible with the same method from
  the parent `Object` class.

0.3.0
-----

* made sure that boolean statements filter parameters are passed as the strings
  `'true'` and `'false'`

* added missing return statements to some methods of the `StatementsFilter`
  class to ensure the fluent interface

* changed the default value of the `display` property of the `Verb` class to
  `null` (was the empty array before) to make it possible to distinguish the
  empty list from the case when the `display` property was omitted

* added the possibility to attach an IRL to an activity definition that acts as
  a reference to a document that contains human-readable information about the
  activity

* all values of an activity definition are optional, pass `null` to omit them

* all values of a result are optional, pass `null` to omit them

* throw an exception when not existing document data is accessed instead of
  failing with a PHP notice

* all values of a score are optional, pass `null` to omit them

* values passed to the constructor of the `Score` class are no longer cast to
  `float`

0.2.0
-----

* added a dedicated class to refer to inverse functional identifiers, refer to
  the upgrade file for more detailed information

* marked the `Statement` class as final

0.1.0
-----

This is the first release containing immutable and final classes that reflect
all parts of Experience API statements.

This package replaces the `xabbuh/xapi-model` package which is now deprecated
and should no longer be used.
