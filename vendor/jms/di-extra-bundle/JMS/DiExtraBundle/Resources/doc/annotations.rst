Annotations
-----------

@Inject
~~~~~~~~~
This marks a property, or parameter for injection:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Inject;

    class Controller
    {
        /**
         * @Inject("security.context", required = false)
         */
        private $securityContext;
        
        /**
         * @Inject("request", strict = false)
         */
        private $request;
        
        /**
         * @Inject("%kernel.cache_dir%")
         */
        private $cacheDir;
        
        /**
         * @Inject
         */
        private $session;
    }

.. tip :: 

    If you do not specify the service explicitly, we will try to guess it based on the name
    of the property or the parameter.

    The "strict" option can be passed to false to avoid exceptions of type ``Symfony\Component\DependencyInjection\Exception\ScopeCrossingInjectionException``, if the scope of the injected service is different than the current one (for example request, or prototype).

@InjectParams
~~~~~~~~~~~~~~~
This marks the parameters of a method for injection:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Inject;
    use JMS\DiExtraBundle\Annotation\InjectParams;
    use JMS\DiExtraBundle\Annotation\Service;

    /**
     * @Service
     */
    class Listener
    {
        /**
         * @InjectParams({
         *     "em" = @Inject("doctrine.entity_manager")
         * })
         */
        public function __construct(EntityManager $em, Session $session)
        {
            // ...
        }
    }
    
If you don't define all parameters in the param map, we will try to guess which services
should be injected into the remaining parameters based on their name.

@Service
~~~~~~~~
Marks a class as service:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Service;

    /**
     * @Service("some.service.id", parent="another.service.id", public=false)
     */
    class Listener
    {
    }

If you do not explicitly define a service id, then we will generated a sensible default
based on the fully qualified class name for you.

@Tag
~~~~
Adds a tag to the service:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Service;
    use JMS\DiExtraBundle\Annotation\Tag;

    /**
     * @Service
     * @Tag("doctrine.event_listener", attributes = {"event" = "postGenerateSchema", lazy=true})
     */
    class Listener
    {
        // ...
    }

@Observe
~~~~~~~~
Automatically registers a method as listener to a certain event:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Observe;
    use JMS\DiExtraBundle\Annotation\Service;

    /**
     * @Service
     */
    class RequestListener
    {
        /**
         * @Observe("kernel.request", priority = 255)
         */
        public function onKernelRequest()
        {
            // ...
        }
    }

@Validator
~~~~~~~~~~
Automatically registers the given class as constraint validator for the Validator component:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation\Validator;
    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\ConstraintValidator;
    
    /**
     * @Validator("my_alias")
     */
    class MyValidator extends ConstraintValidator
    {
        // ...
    }
    
    class MyConstraint extends Constraint
    {
        // ...
        public function validatedBy()
        {
            return 'my_alias';
        }
    }

The @Validator annotation also implies the @Service annotation if you do not specify it explicitly.
The alias which is passed to the @Validator annotation must match the string that is returned from
the ``validatedBy`` method of your constraint.

@FormType
~~~~~~~~~
Automatically, registers the given class as a form type with Symfony2's Form Component.

.. code-block :: php

    <?php
    
    use JMS\DiExtraBundle\Annotation\FormType;
    use Symfony\Component\Form\AbstractType;
    
    /**
     * @FormType
     */
    class MyFormType extends AbstractType
    {
        // ...
        
        public function getName()
        {
            return 'my_form';
        }
    }

    // Controller.php
    $form = $this->formFactory->create('my_form');
    
.. note :: 

    ``@FormType`` implies ``@Service`` if not explicitly defined.
    
@DoctrineListener or @DoctrineMongoDBListener
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Automatically, registers the given class as a listener with the Doctrine ORM or Doctrine MongoDB ODM:

.. code-block :: php

    <?php
    
    use JMS\DiExtraBundle\Annotation\DoctrineListener;
    
    /**
     * @DoctrineListener(
     *     events = {"prePersist", "preUpdate"}, 
     *     connection = "default", 
     *     lazy = true, 
     *     priority = 0,
     * )
    class MyListener
    {
        // ...
    }

.. note ::

    ``@DoctrineListener`` implies ``@Service`` if not explicitly defined.    

