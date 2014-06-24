Doctrine Integration
====================

.. versionadded : 1.1
    Doctrine Integration was added.

Configuration
-------------
Doctrine integration is enabled by default. However, you can easily disable it
in your configuration:

.. configuration-block ::

    .. code-block :: yaml

        jms_di_extra:
            doctrine_integration: false

    .. code-block :: xml

        <jms-di-extra doctrine-integration="false" />


Injecting Dependencies Into Repositories
----------------------------------------
If you have enabled Doctrine integration, you can now inject dependencies into
repositories using annotations:

.. code-block :: php

    use JMS\DiExtraBundle\Annotation as DI;

    class MyRepository extends EntityRepository
    {
        private $uuidGenerator;

        /**
         * @DI\InjectParams({
         *     "uuidGenerator" = @DI\Inject("my_uuid_generator"),
         * })
         */
        public function setUuidGenerator(UUidGenerator $uuidGenerator)
        {
            $this->uuidGenerator = $uuidGenerator;
        }

        // ...
    }

.. note ::

    If you do not want to use annotations, you can also implement
    ``Symfony\Component\DependencyInjection\ContainerAwareInterface`` in your
    repositories to receive the entire service container.