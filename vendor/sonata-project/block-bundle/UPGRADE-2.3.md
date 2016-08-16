UPGRADE FROM 2.2 to 2.3
=======================

## Deprecated BlockServiceInterface::setDefaultSettings

`BlockServiceInterface::setDefaultSettings` method is now deprecated.

A new `AbstractBlockService` implementing `BlockServiceInterface` was also introduced to prevent BC.

A BlockService should now extends `AbstractBlockService` and use `configureSettings` method.

Before:

```php
use Sonata\BlockBundle\Block\BlockServiceInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TextBlockService implements BlockServiceInterface
{
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        // ...
    }
}
```

After:

```php
use Sonata\BlockBundle\Block\AbstractBlockService;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextBlockService extends AbstractBlockService
{
    public function configureSettings(OptionsResolver $resolver)
    {
        // ...
    }
}
```
