.. index::
    single: Handler
    double: Custom Handler; Definition

Request Body Param Converter
============================

Introduction
------------

``SonataCoreBundle`` comes with a ``RequestBodyParamConverter`` which is extending the ``FOS\RestBundle\Request\RequestBodyParamConverter20`` one.

The main reason of why this class is overrided is that our model serialized classes are marked as ``abstract`` and cannot be instanciated.

This class updates the param converter/configuration class given with the one defined in bundle configuration (for example: ``Application\Sonata\NewsBundle\Entity\Post``).

Define your own handler
-----------------------

You can define your own handler like this:

.. code-block:: xml

    <service id="acme.my_bundle.converter.request_body" class="Sonata\CoreBundle\Request\RequestBodyParamConverter">
        <argument type="service" id="fos_rest.serializer" />
        <argument>%fos_rest.serializer.exclusion_strategy.groups%</argument>
        <argument>%fos_rest.serializer.exclusion_strategy.version%</argument>
        <argument type="service" id="fos_rest.validator" on-invalid="ignore" />
        <argument>%fos_rest.converter.request_body.validation_errors_argument%</argument>
        <argument />

        <tag name="request.param_converter" converter="my_bundle.request_body" />
    </service>

Sixth argument should contain an array of classes available in the bundle, for instance the followings:

* ``Application\Sonata\NewsBundle\Entity\Comment`` (extends abstract class ``Sonata\NewsBundle\Model\Comment``);
* ``Application\Sonata\NewsBundle\Entity\Post`` (extends abstract class ``Sonata\NewsBundle\Model\Post``).

Assuming you have a ``$config`` array with your configured classes. In a bundle extension, you can add your own classes this way:

.. code-block:: php

    <?php

    $container->getDefinition('acme.mybundle.converter.request_body')
        ->replaceArgument(5, array(
            $config['class']['comment'],
            $config['class']['post']
        ))
    ;

Once it's done, you can use your ``@ParamConverter`` annotation:

.. code-block:: php

    <?php

    /**
     * @ParamConverter("post", class="Sonata\NewsBundle\Model\Post", converter="my_bundle.request_body")
     */
    public function myAction($post) {
        ...
    }