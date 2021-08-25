CHANGELOG
=========

2.1.0
-----

* added missing support for statement versions

* dropped support for PHP < 5.6

* allow `2.x` releases of the `php-xapi/model` package too

* allow `3.x` releases of the `php-xapi/model` package too

2.0.0
-----

Added support for deserializing raw attachment contents. In order to achieve this
an optional `$attachments` argument has been added to the `deserializeStatement()`
and `deserializeStatements()` methods of the `StatementSerializer` class.

When being passed, this argument must be an array mapping SHA-2 hashes to an
array which in turn maps the `type` and `content` keys to the attachment's
content type and raw content data respectively.

1.0.0
-----

The is the first stable release using the Symfony Serializer component to
provide an implementation of the API described by the `php-xapi/serializer`
package.

Apart from dependency versions being bumped to stable versions this release
provides the same features as `0.1.0`.
