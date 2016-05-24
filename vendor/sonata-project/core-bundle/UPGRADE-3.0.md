UPGRADE FROM 2.X to 3.0
=======================

### TranslatableChoiceType was removed

Form type "sonata_type_translatable_choice" (TranslatableChoiceType) was removed. Use form type "choice" (ChoiceType) with "translation_domain" option instead.

Before:

```php
class FooAdmin extends Admin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('articleAuthor', 'sonata_type_translatable_choice',
            array(
                'catalogue'=>'FooAdminBundle',
                'choices'=>array(0=>'no', 1=>'yes')
            )
        );
    }
}
```

After:

```php
class FooAdmin extends Admin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('articleAuthor', 'choice',
            array(
                'translation_domain'=>'FooAdminBundle',
                'choices'=>array(0=>'no', 1=>'yes')
            )
        );
    }
}
```

## ``DoctrineORMSerializationType`` and ``StatusType``

These classes will be remove use respectively ``BaseDoctrineORMSerializationType`` and ``BaseStatusType``