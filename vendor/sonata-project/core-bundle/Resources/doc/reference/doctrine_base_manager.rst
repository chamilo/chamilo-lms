Doctrine base entity manager
============================

The bundle comes with an abstract class for your entities managers ``Sonata\CoreBundle\Entity\DoctrineBaseManager``.

Use it in your managers
-----------------------
You just have to extends ``Sonata\CoreBundle\Entity\DoctrineBaseManager` in your managers :

.. code-block:: php

    <?php

    namespace Acme\Bundle\Entity;

    use Sonata\CoreBundle\Model\BaseEntityManager;

    class ProductManager extends BaseEntityManager
    {

    }
