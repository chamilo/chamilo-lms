Annotations
-----------
@PreAuthorize
~~~~~~~~~~~~~
This annotation lets you define an expression (see the expression language
paragraph) which is executed prior to invoking a method:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

    class MyService
    {
        /** @PreAuthorize("hasRole('A') or (hasRole('B') and hasRole('C'))") */
        public function secureMethod()
        {
            // ...
        }
    }

.. tip ::

    If you like to secure all actions of the controller with the same rule, you
    may also specify @PreAuthorize on the class itself. Caution though, this
    rule is only applied to the methods which are declared in the class.

@Secure
~~~~~~~
This annotation lets you define who is allowed to invoke a method:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Annotation\Secure;

    class MyService
    {
        /**
         * @Secure(roles="ROLE_USER, ROLE_FOO, ROLE_ADMIN")
         */
        public function secureMethod()
        {
            // ...
        }
    }

@SecureParam
~~~~~~~~~~~~
This annotation lets you define restrictions for parameters which are passed to
the method. This is only useful if the parameters are domain objects:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Annotation\SecureParam;

    class MyService
    {
        /**
         * @SecureParam(name="comment", permissions="EDIT, DELETE")
         * @SecureParam(name="post", permissions="OWNER")
         */
        public function secureMethod($comment, $post)
        {
            // ...
        }
    }

@SecureReturn
~~~~~~~~~~~~~
This annotation lets you define restrictions for the value which is returned by
the method. This is also only useful if the returned value is a domain object:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Annotation\SecureReturn;

    class MyService
    {
        /**
         * @SecureReturn(permissions="VIEW")
         */
        public function secureMethod()
        {
            // ...

            return $domainObject;
        }
    }

@RunAs
~~~~~~
This annotation lets you specifiy roles which are added only for the duration
of the method invocation. These roles will not be taken into consideration
for before, or after invocation access decisions.

This is typically used to implement a two-tier service layer where you have
public and private services, and private services are only to be invoked
through a specific public service:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Annotation\Secure;
    use JMS\SecurityExtraBundle\Annotation\RunAs;

    class MyPrivateService
    {
        /**
         * @Secure(roles="ROLE_PRIVATE_SERVICE")
         */
        public function aMethodOnlyToBeInvokedThroughASpecificChannel()
        {
            // ...
        }
    }

    class MyPublicService
    {
        protected $myPrivateService;

        /**
         * @Secure(roles="ROLE_USER")
         * @RunAs(roles="ROLE_PRIVATE_SERVICE")
         */
        public function canBeInvokedFromOtherServices()
        {
            return $this->myPrivateService->aMethodOnlyToBeInvokedThroughASpecificChannel();
        }
    }

@SatisfiesParentSecurityPolicy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
This must be defined on a method that overrides a method which has security metadata.
It is there to ensure that you are aware the security of the overridden method cannot
be enforced anymore, and that you must copy over all annotations if you want to keep
them.
