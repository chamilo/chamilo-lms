# Doctrine2 Behaviors

[![Build Status](https://travis-ci.org/KnpLabs/DoctrineBehaviors.svg?branch=master)](http://travis-ci.org/KnpLabs/DoctrineBehaviors)


This PHP `>=5.4` library is a collection of traits and interfaces
that add behaviors to Doctrine2 entities and repositories.

It currently handles:

 * [blameable](#blameable)
 * [filterable](#filterable)
 * [geocodable](#geocodable)
 * joinable
 * [loggable](#loggable)
 * [sluggable](#sluggable)
 * [softDeletable](#softDeletable)
 * sortable
 * [timestampable](#timestampable)
 * [translatable](#translatable)
 * [tree](#tree)

## This project is looking for maintainers

We realize we don't have so much time anymore to maintain this project as it should be maintained.
Therefore we are looking for maintainers. Open an issue if you want to keep working on this.

## Notice:

Some behaviors (translatable, timestampable, softDeletable, blameable, geocodable) need Doctrine subscribers in order to work.
Make sure to activate them by reading the [Subscribers](#subscribers) section.

## Installation

```
composer require knplabs/doctrine-behaviors:~1.1
```

## Configuration
By default, when integrated with Symfony, all subscribers are enabled (if you don't specify any configuration for the bundle).
But you can enable behaviors you need in a whitelist manner:
```yaml
knp_doctrine_behaviors:
    blameable:      false
    geocodable:     ~     # Here null is converted to false
    loggable:       ~
    sluggable:      true
    soft_deletable: true
    # All others behaviors are disabled
```

<a name="subscribers" id="subscribers"></a>
## Subscribers

If you use symfony2, you can easily register them in:

- *Recommended way:*

Add to AppKernel

```php
class AppKernel
{
    function registerBundles()
    {
        $bundles = array(
            //...
            new Knp\DoctrineBehaviors\Bundle\DoctrineBehaviorsBundle(),
            //...
        );

        //...

        return $bundles;
    }
}

```

- *Deprecated way:*
Importing a service definition file:

``` yaml
    # app/config/config.yml
    imports:
        - { resource: ../../vendor/knplabs/doctrine-behaviors/config/orm-services.yml }

```

You can also register them using doctrine2 api:


``` php
<?php

$em->getEventManager()->addEventSubscriber(new \Knp\DoctrineBehaviors\ORM\Translatable\TranslatableSubscriber);
// register more if needed

```


## Usage

All you have to do is to define a Doctrine2 entity and use traits:

``` php
<?php

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Entity(repositoryClass="CategoryRepository")
 */
class Category implements ORMBehaviors\Tree\NodeInterface, \ArrayAccess
{
    use ORMBehaviors\Blameable\Blameable,
        ORMBehaviors\Geocodable\Geocodable,
        ORMBehaviors\Loggable\Loggable,
        ORMBehaviors\Sluggable\Sluggable,
        ORMBehaviors\SoftDeletable\SoftDeletable,
        ORMBehaviors\Sortable\Sortable,
        ORMBehaviors\Timestampable\Timestampable,
        ORMBehaviors\Translatable\Translatable,
        ORMBehaviors\Tree\Node
    ;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;
}

```


For some behaviors like tree, you can use repository traits:

``` php
<?php

use Doctrine\ORM\EntityRepository;
use Knp\DoctrineBehaviors\ORM as ORMBehaviors;

class CategoryRepository extends EntityRepository
{
    use ORMBehaviors\Tree\Tree,
}

```

Voila!

You now have a working `Category` that behaves like:

<a name="tree" id="tree"></a>
### tree:

``` php
<?php

    $category = new Category;
    $category->setId(1); // tree nodes need an id to construct path.
    $child = new Category;
    $child->setId(2);

    $child->setChildNodeOf($category);

    $em->persist($child);
    $em->persist($category);
    $em->flush();

    $root = $em->getRepository('Category')->getTree();

    $root->getParentNode(); // null
    $root->getChildNodes(); // ArrayCollection
    $root[0][1]; // node or null
    $root->isLeafNode(); // boolean
    $root->isRootNode(); // boolean

```

> it is possible to use another identifier than `id`, simply override `getNodeId` and return your custom identifier (works great in combination with `Sluggable`)

<a name="translatable" id="translatable"></a>
### translatable:

If you're working on a `Category` entity, the `Translatable` behavior expects a **CategoryTranslation** entity in the 
same folder of Category entity by default.

The default naming convention (or its customization via trait methods) avoids you to manually handle entity associations.
It is handled automatically by the TranslationSubscriber.

In order to use the Translatable trait, you will have to create this `CategoryTranslation` entity.

``` php
<?php

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Entity
 */
class CategoryTranslation
{
    use ORMBehaviors\Translatable\Translation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $description;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string
     * @return null
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param  string
     * @return null
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
```
The corresponding Category entity needs to `use ORMBehaviors\Translatable\Translatable;`
and should only contain fields that you do not need to translate.

``` php
<?php

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Entity
 */
class Category
{
    use ORMBehaviors\Translatable\Translatable;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $someFieldYouDoNotNeedToTranslate;
}
```


After updating the database, ie. with `./console doctrine:schema:update --force`, 
you can now work on translations using `translate` or `getTranslations` methods.

``` php
<?php

    $category = new Category;
    $category->translate('fr')->setName('Chaussures');
    $category->translate('en')->setName('Shoes');
    $em->persist($category);

    // In order to persist new translations, call mergeNewTranslations method, before flush
    $category->mergeNewTranslations();

    $category->translate('en')->getName();

```

#### Override

In case you prefer to use a different class name for the translation entity, 
or want to use a separate namespace, you have 2 ways :

If you want to define a custom translation entity class name globally :  
Override the trait `Translatable` and his  method `getTranslationEntityClass` 
and the trait `Translation` and his method `getTranslatableEntityClass` in the translation entity. 
If you override one, you also need to override the other to return the inverse class.

Example: Let's say you want to create a sub namespace AppBundle\Entity\Translation to stock translations classes 
then put overrided traits in that folder.

``` php
<?php
namespace AppBundle\Entity\Translation;

use Knp\DoctrineBehaviors\Model\Translatable\Translatable;
use Symfony\Component\PropertyAccess\PropertyAccess;

trait TranslatableTrait
{
    use Translatable;

    /**
     * @inheritdoc
     */
    public static function getTranslationEntityClass()
    {
        $explodedNamespace = explode('\\', __CLASS__);
        $entityClass = array_pop($explodedNamespace);
        return '\\'.implode('\\', $explodedNamespace).'\\Translation\\'.$entityClass.'Translation';
    }
}
```

``` php
<?php
namespace AppBundle\Entity\Translation;

use Knp\DoctrineBehaviors\Model\Translatable\Translation;

trait TranslationTrait
{
    use Translation;

    /**
     * @inheritdoc
     */
    public static function getTranslatableEntityClass()
    {
        $explodedNamespace = explode('\\', __CLASS__);
        $entityClass = array_pop($explodedNamespace);
        // Remove Translation namespace
        array_pop($explodedNamespace);
        return '\\'.implode('\\', $explodedNamespace).'\\'.substr($entityClass, 0, -11);
    }
}
```

If you use that way make sure you override trait parameters of DoctrineBehaviors :

``` yaml
parameters:
    knp.doctrine_behaviors.translatable_subscriber.translatable_trait: AppBundle\Entity\Translation\TranslatableTrait
    knp.doctrine_behaviors.translatable_subscriber.translation_trait: AppBundle\Entity\Translation\TranslationTrait
```

If you want to define a custom translation entity class name just for a single translatable class :  
Override the trait method `getTranslationEntityClass` in the translatable entity and `getTranslatableEntityClass`
in the translation entity. If you override one, you also need to override the other to return the inverse class.


#### guess the current locale

You can configure the way the subscriber guesses the current locale, by giving a callable as its first argument.
This library provides a callable object (`Knp\DoctrineBehaviors\ORM\Translatable\CurrentLocaleCallable`) that returns the current locale using Symfony2.


#### proxy translations

An extra feature allows you to proxy translated fields of a translatable entity.

You can use it in the magic `__call` method of you translatable entity
so that when you try to call `getName` (for example) it will return you the translated value of the name for current locale:

``` php
<?php

    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }
    
    // or do it with PropertyAccessor that ships with Symfony SE
    // if your methods don't take any required arguments
    public function __call($method, $arguments)
    {
        return \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor()->getValue($this->translate(), $method);
    }
```

<a name="softDeletable" id="softDeletable"></a>
### soft-deletable

``` php
<?php

    $category = new Category;
    $em->persist($category);
    $em->flush();

    // get id
    $id = $category->getId();

    // now remove it
    $em->remove($category);
    $em->flush();

    // hey, I'm still here:
    $category = $em->getRepository('Category')->findOneById($id);

    // but I'm "deleted"
    $category->isDeleted(); // === true

    // restore me
    $category->restore();

    //look ma, I am back
    $category->isDeleted(); // === false

    //do not forget to call flush method to apply the change
    $em->flush();
```

``` php
<?php

    $category = new Category;
    $em->persist($category);
    $em->flush();

    // I'll delete you tomorrow
    $category->setDeletedAt((new \DateTime())->modify('+1 day'));

    // OK, I'm here
    $category->isDeleted(); // === false

    /*
     *  24 hours later...
     */

    // OK, I'm deleted
    $category->isDeleted(); // === true
```

<a name="timestampable" id="timestampable"></a>
### timestampable

``` php
<?php

    $category = new Category;
    $em->persist($category);
    $em->flush();

    $id = $category->getId();
    $category = $em->getRepository('Category')->findOneById($id);

    $category->getCreatedAt();
    $category->getUpdatedAt();

```

If you wish to change the doctrine type of the database fields that will be created for timestampable models you can
set the following parameter like so:

``` yaml
parameters:
    knp.doctrine_behaviors.timestampable_subscriber.db_field_type: datetimetz
```

`datetimetz` here is a useful one to use if you are working with a Postgres database, otherwise you may encounter some
timezone issues. For more information on this see: 
<a href="http://doctrine-dbal.readthedocs.org/en/latest/reference/known-vendor-issues.html#datetime-datetimetz-and-time-types">http://doctrine-dbal.readthedocs.org/en/latest/reference/known-vendor-issues.html#datetime-datetimetz-and-time-types</a>

The default type is `datetime`.

<a name="blameable" id="blameable"></a>
### blameable

Blameable is able to track creators and updators of a given entity.
A blameable [callable](#callables) is used to get the current user from your application.

In the case you are using a Doctrine Entity to represent your users, you can configure the subscriber
to manage automatically the association between this user entity and your entites.

Using symfony2, all you have to do is to configure the DI parameter named `%knp.doctrine_behaviors.blameable_subscriber.user_entity%` with a fully qualified namespace,
for example:

    # app/config/config.yml
    parameters:
        knp.doctrine_behaviors.blameable_subscriber.user_entity: AppBundle\Entity\User

Then, you can use it like that:

``` php
<?php

    $category = new Category;
    $em->persist($category);

    // instances of %knp.doctrine_behaviors.blameable_subscriber.user_entity%
    $creator = $category->getCreatedBy();
    $updater = $category->getUpdatedBy();

```

<a name="loggable" id="loggable"></a>
### loggable

Loggable is able to track lifecycle modifications and log them using any third party log system.
A loggable [callable](#callables) is used to get the logger from anywhere you want.

``` php
<?php

/**
 * @ORM\Entity
 */
class Category
{
    use ORMBehaviors\Loggable\Loggable;

    // you can override the default log messages defined in trait:
    public function getUpdateLogMessage(array $changeSets = [])
    {
        return 'Changed: '.print_r($changeSets, true);
    }

    public function getRemoveLogMessage()
    {
        return 'removed!';
    }
}

```

These messages are then passed to the configured callable.
You can define your own, by passing another callable to the LoggableSubscriber:


``` php
<?php

$em->getEventManager()->addEventSubscriber(
    new \Knp\DoctrineBehaviors\ORM\Loggable\LoggableSubscriber(
        new ClassAnalyzer,
        function($message) {
            // do stuff with message
        }
    )
);


```

If you're using symfony, you can also configure which callable to use:

    // app/config/config.yml
    parameters:
        knp.doctrine_behaviors.loggable_subscriber.logger_callable.class: Your\InvokableClass


<a name="geocodable" id="geocodable"></a>
### geocodable

Geocodable Provides extensions to PostgreSQL platform in order to work with cube and earthdistance extensions.

It allows you to query entities based on geographical coordinates.
It also provides an easy entry point to use 3rd party libraries like the excellent [geocoder](https://github.com/willdurand/Geocoder) to transform addresses into latitude and longitude.


``` php
<?php

    $geocoder = new \Geocoder\Geocoder;
    // register geocoder providers

    // $subscriber instanceof GeocodableSubscriber (add "knp.doctrine_behaviors.geocodable_subscriber" into your services.yml)
    $subscriber->setGeolocationCallable(function($entity) use($geocoder) {
        $location = $geocoder->geocode($entity->getAddress());
        return new Point(
            $location->getLatitude(),
            $location->getLongitude()
        ));
    });

    $category = new Category;
    $em->persist($category);

    $location = $category->getLocation(); // instanceof Point

    // find cities in a circle of 500 km around point 47 lon., 7 lat.
    $nearCities = $repository->findByDistance(new Point(47, 7), 500);

```

<a name="sluggable" id="sluggable"></a>
### sluggable

Sluggable generates slugs (uniqueness is not guaranteed) for an entity.
Will automatically generate on update/persist (you can disable the on update generation by overriding `getRegenerateSlugOnUpdate` to return false.
You can also override the slug delimiter from the default hyphen by overriding `getSlugDelimiter`.
Slug generation algo can be changed by overriding `generateSlugValue`.
Use cases include SEO (i.e. URLs like http://example.com/post/3/introduction-to-php)
```php
<?php

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Entity
 */
class BlogPost
{
    use ORMBehaviors\Sluggable\Sluggable;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    public function getSluggableFields()
    {
        return [ 'title' ];
    }

    public function generateSlugValue($values)
    {
        return implode('-', $values);
    }
}
```

<a name="filterable" id="filterable"></a>
### filterable:

Filterable can be used at the Repository level

It allows to simple filter our result

Joined filters example:

```php
<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ProductRepository")
 */
class ProductEntity
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="Order", mappedBy="product")
     */
    protected $orders;
}
```

and repository:

```php
<?php

use Knp\DoctrineBehaviors\ORM\Filterable;
use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
    use Filterable\FilterableRepository;

    public function getLikeFilterColumns()
    {
        return ['e:name', 'o:code'];
    }

    public function getEqualFilterColumns()
    {
        return [];
    }

    protected function createFilterQueryBuilder()
    {
        return $this
            ->createQueryBuilder('e')
            ->leftJoin('e.orders', 'o');
    }
}
```

Now we can filtering using:

```php
    $products = $em->getRepository('Product')->filterBy(['o:code' => '21']);
```



<a name="callables" id="callables"></a>
## callables

Callables are used by some subscribers like blameable and geocodable to fill information based on 3rd party system.

For example, the blameable callable can be any symfony2 service that implements  `__invoke` method or any anonymous function, as soon as they return currently logged in user representation (which means everything, a User entity, a string, a username, ...).
For an example of DI service that is invoked, look at the `Knp\DoctrineBehaviors\ORM\Blameable\UserCallable` class.

In the case of geocodable, you can set it as any service that implements `__invoke` or anonymous function that returns a `Knp\DoctrineBehaviors\ORM\Geocodable\Type\Point` object.

## Testing

[Read the documentation for testing ](doc/test.md)
