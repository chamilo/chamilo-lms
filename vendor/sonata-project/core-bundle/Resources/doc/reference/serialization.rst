.. index::
    double: Custom Handlers; Definition

Serialization
=============

Custom handlers
---------------

The bundle comes with a ``BaseSerializerHandler`` to let you customize your serialized entities; this handler is used to serialize/deserialize an entity to/from its id, but you remain free to create your own handler for your specific needs.

Just override ``Sonata\CoreBundle\Serializer\BaseSerializerHandler`` to create a `JMS Serializer` handler.

You can define your handler like this:

.. code-block:: xml

        <service id="acme.serializer.my_entity" class="Acme\Bundle\Serializer\MyEntitySerializerHandler">
            <tag name="jms_serializer.subscribing_handler" />
            <argument type="service" id="acme.manager.my_entity" />
        </service>

To call your handler, you can use a custom type used by `JMS Serializer`, like this:

.. code-block:: xml

        <property name="entity" serialized-name="entity_id" type="my_custom_type" />

And your handler need to specify the type name:

.. code-block:: php

        <?php

        namespace Acme\Bundle\Serializer;

        use Sonata\CoreBundle\Serializer\BaseSerializerHandler;

        class MyEntitySerializerHandler extends BaseSerializerHandler
        {
            /**
             * {@inheritdoc}
             */
            public static function getType()
            {
                return 'my_custom_type';
            }
        }
