.. index::
    single: Doctrine
    single: Managers

Doctrine base entity manager
============================

The bundle comes with an abstract class for your entities and documents managers ``Sonata\CoreBundle\Model\BaseEntityManager``,``Sonata\CoreBundle\Model\BaseDocumentManager`` and ``Sonata\CoreBundle\Model\BasePHPCRManager``.

Use it in your managers
-----------------------
You just have to extend ``Sonata\CoreBundle\Model\BaseEntityManager``, ``Sonata\CoreBundle\Model\BaseDocumentManager`` or ``Sonata\CoreBundle\Model\BasePHPCRManager`` in your managers, for instance:

.. code-block:: php

    <?php

    namespace Acme\Bundle\Entity;

    use Sonata\CoreBundle\Model\BaseEntityManager;

    class ProductManager extends BaseEntityManager
    {

    }

