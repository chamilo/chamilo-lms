Usage
=====

Non-Controller Classes
----------------------
Non-controller classes are configured, and managed by Symfony's DIC just like any
other service that you configure using YML, XML, or PHP. The only difference is
that you can do it via annotations which is a lot more convenient.

You can use these annotations on services (for examples, see below):
@Service, @Inject, @InjectParams, @Observe, @Tag

Note that you cannot use the @Inject annotation on private, or protected properties.
Likewise, the @InjectParams annotation does not work on protected, or private methods.

Controllers
-----------
Controllers are a special type of class which is also treated specially by this
bundle. The most notable difference is that you do not need to define these
classes as services. Yes, no services, but don't worry you can still use all of
the DIC's features, and even some more.

Constructor/Setter Injection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block :: php

    <?php
   
    use JMS\DiExtraBundle\Annotation as DI;
   
    class Controller
    {
        private $em;
        private $session;
    
        /**
         * @DI\InjectParams({
         *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
         *     "session" = @DI\Inject("session")
         * })
         */
        public function __construct($em, $session)
        {
            $this->em = $em;
            $this->session = $session;
        }
        // ... some actions
    }
    
.. note :: 

    Constructor Injection is not possible when a parent definition
    also defines a constructor which is configured for injection.

Property Injection
~~~~~~~~~~~~~~~~~~

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation as DI;
    
    class Controller
    {
        /** @DI\Inject("doctrine.orm.entity_manager") */
        private $em;
        
        /** @DI\Inject("session") */
        private $session;
    }

.. note ::

    Injecting into private, or protected properties is only supported on controllers.
    
Method/Getter Injection
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block :: php

    <?php
    
    use JMS\DiExtraBundle\Annotation as DI;
    
    class Controller
    {
        public function myAction()
        {
            // ...
            if ($condition) {
                $mailer = $this->getMailer();
            }
        }
    
        /** @DI\LookupMethod("mailer") */
        protected function getMailer() { /* empty body here */ }
    }

You can use this type of injection if you have a dependency that you do not
always need in the controller, and which is costly to initialize, like the
mailer in the example above.
