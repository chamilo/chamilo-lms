# UPGRADE

### 2.1 to 2.2

The responsibility of generating routes and assets path have been moved to a dedicated templating helper for a better
decoupling. The core assets helper and assets version trimer helper dependencies have been removed from all
managers and have been moved to this helper. Then, all constructors have been updated accordingly and all related
getter/setter have been removed. Additionally, the form type have been updated the same way and the same dependencies
have been removed. Then, its constructor and the related getter/setter have been removed.

So, the affected classes are:

 * `Ivory\CKEditorBundle\Model\ConfigManager`
 * `Ivory\CKEditorBundle\Model\PluginManager`
 * `Ivory\CKEditorBundle\Model\TemplateManager`
 * `Ivory\CKEditorBundle\Form\Type\CKEditorType`

The PHP and Twig templates have been refactored to use the new templating helper.

### 1.0 to 1.1 - 2.0 to 2.1

The `toolbar` & `uiColor` options have been removed in favor of the `config` option which allows a more flexible
configuration.

Before:

``` php
$builder->add('field', 'ckeditor', array(
    'uiColor' => '#ffffff',
    'toolbar'  => array(
        // ...
    ),
));
```

After:

``` php
$builder->add('field', 'ckeditor', array(
    'config' => array(
        'uiColor' => '#ffffff',
        'toolbar'  => array(
            // ...
        ),
        // Other options...
    ),
));
```
