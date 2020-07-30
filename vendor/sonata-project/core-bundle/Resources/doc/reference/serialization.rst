.. index::
    double: Custom Handlers; Definition

Serialization
=============

Custom handlers
---------------

The bundle comes with a ``BaseSerializerHandler`` to let you customize your serialized entities;
this handler is used to serialize/deserialize an entity to/from its id within the defaults
formats ('json', 'xml', 'yml').

The serializer default formats are configurable. You can change them from the configuration file.

.. code-block:: yaml

        sonata_core:
            serializer:
                formats: ['json', 'xml', 'yml']

You can set these formats to a different array or you can add another format to these formats by using
``BaseSerializerHandler`` methods ``setFormats`` and ``addFormat``

You are free to create your own handler for your specific needs.

Just override ``Sonata\CoreBundle\Serializer\BaseSerializerHandler`` to create a `JMS Serializer` handler.

You can define your handler like this:

.. code-block:: xml

        <service id="app.serializer.post" class="AppBundle\Serializer\PostSerializerHandler">
            <tag name="jms_serializer.subscribing_handler" />
            <argument type="service" id="app.manager.post" />
        </service>

To call your handler, you can use a custom type used by `JMS Serializer`, like this:

.. code-block:: xml

        <property name="post" serialized-name="entity_id" type="post_type" />

And your handler need to specify the type name:

.. code-block:: php

        <?php
        // src/AppBundle/Serializer/PostSerializerHandler.php

        namespace AppBundle\Serializer;

        use Sonata\CoreBundle\Serializer\BaseSerializerHandler;

        class PostSerializerHandler extends BaseSerializerHandler
        {
            public static function getType()
            {
                return 'post_type';
            }
        }
