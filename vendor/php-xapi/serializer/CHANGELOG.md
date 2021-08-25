CHANGELOG
=========

2.2.0
-----

* allow `3.x` releases of the `php-xapi/model` package too

* dropped support for HHVM

2.1.0
-----

* allow `2.x` releases of the `php-xapi/model` package too

2.0.0
-----

Raw attachment content data must be passed in order to make it possible for
serializer implementations to populate the `$content` attribute of `Attachment`
objects during deserialization.

In order to achieve this an optional `$attachments` argument has been added
to the `StatementResultSerializerInterface::deserializeStatementResult()`,
and the `deserializeStatement()`  and `deserializeStatements()` methods of
the `StatementSerializerInterface`.

When being passed, this argument must be an array mapping SHA-2 hashes to an
array which in turn maps the `type` and `content` keys to the attachment's
content type and raw content data respectively.

1.0.0
-----

This is the first stable release of the Experience API serialization API.
In terms of functions, there are no differences to the `0.4.0` release, but
required versions of the `php-xapi` packages each have been bumped to `^1.0`.

0.4.0
-----

* The serializer implementation has been separated from its API definition.
  This package now no longer ships with an implementation.

  The Symfony Serializer component integration has been moved to the separate
  [php-xapi/symfony-serializer package](https://github.com/php-xapi/symfony-serializer).

  A default implementation of the `SerializerRegistryInterface` is still part
  of the `php-xapi/serializer` package though.

  This package ships with the following interfaces that must be implemented
  by packages that want to provide the xAPI serialization functionality:

  * `ActorSerializerInterface`
  * `DocumentDataSerializerInterface`
  * `StatementResultSerializerInterface`
  * `StatementSerializerInterface`
  * `StatementFactoryInterface`

  Implementors of the API provided by this package are advised to add the
  `php-xapi/serializer-implementation` package to the `provide` section of
  their `composer.json` file.

  The `Tests` subnamespace of this package contains a set of base abstract
  PHPUnit test classes integrators can use to make sure that their implementation
  adheres to the API specified by the `php-xapi/serializer` package.

* Added a `SerializerFactoryInterface` that abstracts the creation of serializer
  instances.

* The `SerializerRegistry` class is now final. If you need custom behavior
  inside the serializer registry, create your own implementation of the
  `SerializerRegistryInterface`.

0.3.0
-----

* Normalization and denormalization support for `IRI` and `IRL` instances
  where they have been introduced in the `php-xapi/model` package.

* Fixed that context attributes are no longer ignored when statements are
  normalized/denormalized.

* Added support for normalizing/denormalizing activity definition extensions.

* Added support for normalizing/denormalizing statement activity interactions.

* Added support for normalizing/denormalizing `LanguageMap` instances which
  is now the data type for the `$display` property of the `Verb` class as
  well as for the `$name` and `$description` properties of the `Definition`
  class.

* Updated how statement ids are normalized/denormalized to reflect the introduction
  of the `StatementId` value object in the `php-xapi/model` package.

* Added support for normalizing and denormalizing statement contexts, context
  activities, and extensions.

* Properly denormalize statement objects (activities, agents, groups, statement
  references, and sub statements).

0.2.2
-----

* Added support for (de)serializing a statement's `timestamp` and `stored`
  properties.

0.2.1
-----

* The object type is now optional. When the `objectType` key is omitted while an
  object is deserialized, it is to be assumed that the type of the denormalized
  object is activity.

* Empty PHP arrays are now dumped as JSON objects instead of empty lists.

* fixed the key of the mbox SHA1 sum property when denormalizing actors

* fixed deserializing incomplete agent objects that are missing the required
  IRI (the `ActorNormalizer` wil now throw an exception)

* add a `FilterNullValueNormalizer` that prevents `null` values from being
  serialized

* empty group member lists are not normalized, but the property will be omitted

* ignore nullable result properties when they are not set during normalization
  and denormalization

0.2.0
-----

* made the package compatible with version 0.2 of the `php-xapi/model` package

* replaced the JMS Serializer with the Symfony Serializer component

0.1.1
-----

* restrict dependency versions to not pull in potentially BC breaking package
  versions

0.1.0
-----

First release leveraging the JMS serializer library to convert xAPI model
objects into JSON strings confirming to the xAPI specs and vice versa.

This package replaces the `xabbuh/xapi-serializer` package which is now deprecated
and should no longer be used.
