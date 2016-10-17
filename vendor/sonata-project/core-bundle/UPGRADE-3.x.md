UPGRADE 3.x
===========

### Tests

All files under the ``Tests`` directory are now correctly handled as internal test classes.
You can't extend them anymore, because they are only loaded when running internal tests.
More information can be found in the [composer docs](https://getcomposer.org/doc/04-schema.md#autoload-dev).

### Deprecated exporter class and service

The exporter class and service are now deprecated in favor of very similar equivalents from the
[`sonata-project/exporter`](https://github.com/sonata-project/exporter) library,
which are available since version 1.6.0,
if you enable the bundle as described in the documentation.
