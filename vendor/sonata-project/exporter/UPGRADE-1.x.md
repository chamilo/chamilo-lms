UPGRADE 1.x
===========

UPGRADE FROM 1.5 to 1.6
=======================

## Deprecated AbstractTypedWriterTestCase namespace

The `Test\Writer\AbstractTypedWriterTestCase` class is deprecated. Use `Test\AbstractTypedWriterTestCase` instead.

UPGRADE FROM 1.4 to 1.5
=======================

## Changes in directory structure

The `lib` directory has been renamed to the more standard `src`.

Also, the directory structure of `src` and `test` no longer follows `PSR-0`, but `PSR-4`.
This should not change anything for users since this library is meant to be used with Composer autoloading only.
