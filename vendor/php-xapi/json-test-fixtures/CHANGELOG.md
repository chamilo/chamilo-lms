CHANGELOG
=========

2.0.0
-----

* Attachment fixtures are now objects containing up to two keys. `metadata`
  contains the JSON encoded attachment while `content`, if present, denotes
  the raw attachment content.

1.0.0
-----

This first stable release contains all the official conformance test fixtures
provided by the maintainers of the xAPI spec as JSON encoded files and provides
ways to access them through a PHP API.

This release is equivalent to the 0.2 release series and is considered to
be stable.

0.2.3
-----

* Added a `Statement` fixture defining all properties of a statement.

* Added fixtures for statement attachments.

* Added fixtures for activity definition extensions.

* Added fixtures for activity interaction definitions and interaction components.

* Added missing fixtures for `Account`, `Activity`, `Actor`, `Context`,
  `ContextActivities`, `Extensions`, `Result`, `SubStatement`, `StatementReference`
  and `Verb`.

* Removed dependency on the `php-xapi/model` package. The classes from that
  package are never used.

0.2.2
-----

Synchronized the list of statement fixtures with the test cases of the
`php-xapi/test-fixtures` package.

0.2.1
-----

Do not block the installation of release `0.4` of the `php-xapi/model` package.

0.2.0
-----

Switched to make use of the official conformance test fixtures that are provided
by the maintainers of the xAPI spec.

0.1.0
-----

This is the first release containing JSON test fixtures for actors (agents and
groups), verbs, documents, activities,statements, and statement results.
