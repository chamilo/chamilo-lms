UPGRADE FROM 2.2 to 2.3
=======================

### Listeners' events method renamed

Some events catching method was renamed. Old names was deprecated and will be removed in 3.0.

Before:

```php
class FixCheckboxDataListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_BIND => 'preBind');
    }

    public function preBind(FormEvent $event)
    {
        // ...
    }
}

class ResizeFormListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA    => 'preSetData',
            FormEvents::PRE_BIND        => 'preBind',
            FormEvents::BIND            => 'onBind',
        );
    }

    public function preSetData(FormEvent $event)
    {
        // ...
    }

    public function preBind(FormEvent $event)
    {
        // ...
    }

    public function onBind(FormEvent $event)
    {
        // ...
    }
}
```

After:

```php
class FixCheckboxDataListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    public function preSubmit(FormEvent $event)
    {
        // ...
    }
}

class ResizeFormListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA    => 'preSetData',
            FormEvents::PRE_SUBMIT      => 'preSubmit',
            FormEvents::SUBMIT          => 'onSubmit',
        );
    }

    public function preSetData(FormEvent $event)
    {
        // No change for this method.
        // ...
    }

    public function preSubmit(FormEvent $event)
    {
        // ...
    }

    public function onSubmit(FormEvent $event)
    {
        // ...
    }
}
```

If you are extending one of those classes, please update your code.
