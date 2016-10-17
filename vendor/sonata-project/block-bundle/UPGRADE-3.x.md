UPGRADE 3.x
===========

UPGRADE FROM 3.1 to 3.2
=======================

## Deprecated block classes and interfaces

The `Sonata\BlockBundle\Block\AbstractBlockService` and `Sonata\BlockBundle\Block\BaseBlockService` classes are deprecated.
Use `Sonata\BlockBundle\Block\AbstractBlockService` for normal blocks or `Sonata\BlockBundle\Block\AbstractAdminBlockService` for manageable blocks instead.

The interfaces `Sonata\BlockBundle\Block\BlockServiceInterface` and `Sonata\BlockBundle\Block\BlockAdminServiceInterface` are deprecated.

UPGRADE FROM 3.0 to 3.1
=======================

## Deprecated test classes

The `Tests\Block\Service\FakeTemplating` class is deprecated. Use `Test\FakeTemplating` instead.
This is introduced on 3.1.1 because of a forgotten needed Merge Request.

## Deprecated AbstractBlockServiceTest class

The `Tests\Block\AbstractBlockServiceTest` class is deprecated. Use `Test\AbstractBlockServiceTestCase` instead.

### Tests

All files under the ``Tests`` directory are now correctly handled as internal test classes.
You can't extend them anymore, because they are only loaded when running internal tests.
More information can be found in the [composer docs](https://getcomposer.org/doc/04-schema.md#autoload-dev).
