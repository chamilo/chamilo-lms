Expression-based Authorization Language
#######################################

Introduction
------------
The expression language is a very powerful alternative to the simple attributes
of the security voting system. They allow to perform complex access decision
checks, and because they are compiled down to raw PHP, they are much faster than
the built-in voters. Also they are lazy-loading by nature, so you will also
save some resources for example by not having to initialize the entire ACL system
on each request.

Usage
-----
Programmatic Usage
~~~~~~~~~~~~~~~~~~
You can execute expressions programmatically by using the ``isGranted`` method
of the SecurityContext. Some examples:

.. code-block :: php

    <?php

    use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

    $securityContext->isGranted(array(new Expression('hasRole("A")')));
    $securityContext->isGranted(array(new Expression('hasRole("A") or (hasRole("B") and hasRole("C"))')));
    $securityContext->isGranted(array(new Expression('hasPermission(object, "VIEW")'), $object));
    $securityContext->isGranted(array(new Expression('token.getUsername() == "Johannes"')));

Twig Usage
~~~~~~~~~~
You can check expressions from Twig templates using the ``is_expr_granted``
function. Some examples:

.. code-block :: jinja

    is_expr_granted("hasRole('FOO')")
    is_expr_granted("hasPermission(object, 'VIEW')", object)

Usage in Access Control
~~~~~~~~~~~~~~~~~~~~~~~
You can also use expressions in the ``access_control``:

.. configuration-block ::

    .. code-block :: yaml

        security:
            access_control:
                - { path: ^/foo, access: "hasRole('FOO') and hasRole('BAR')" }

    .. code-block :: xml

        <security>
            <rule path="^/foo" access="hasRole('FOO') and hasRole('BAR')" />
        </security>

Annotation-based Usage
~~~~~~~~~~~~~~~~~~~~~~
See @PreAuthorize in the annotation reference. Please also remember to enable expressions
in your config, otherwise you will get an exception upon checking access.

Reference
---------
+-----------------------------------+--------------------------------------------+
| Expression                        | Description                                |
+===================================+============================================+
| hasRole('ROLE')                   | Checks whether the token has a certain     |
|                                   | role.                                      |
+-----------------------------------+--------------------------------------------+
| hasAnyRole('ROLE1', 'ROLE2', ...) | Checks whether the token has any of the    |
|                                   | given roles.                               |
+-----------------------------------+--------------------------------------------+
| isAnonymous()                     | Checks whether the token is anonymous.     |
+-----------------------------------+--------------------------------------------+
| isRememberMe()                    | Checks whether the token is remember me.   |
+-----------------------------------+--------------------------------------------+
| isFullyAuthenticated()            | Checks whether the token is fully          |
|                                   | authenticated.                             |
+-----------------------------------+--------------------------------------------+
| isAuthenticated()                 | Checks whether the token is not anonymous. |
+-----------------------------------+--------------------------------------------+
| hasPermission(*var*, 'PERMISSION')| Checks whether the token has the given     |
|                                   | permission for the given object (requires  |
|                                   | the ACL system).                           |
+-----------------------------------+--------------------------------------------+
| token                             | Variable that refers to the token          |
|                                   | which is currently in the security context.|
+-----------------------------------+--------------------------------------------+
| user                              | Variable that refers to the user           |
|                                   | which is currently in the security context.|
+-----------------------------------+--------------------------------------------+
| object                            | Variable that refers to the object for     |
|                                   | which access is being requested.           |
+-----------------------------------+--------------------------------------------+
| #*paramName*                      | Any identifier prefixed with # refers to   |
|                                   | a parameter of the same name that is passed|
|                                   | to the method where the expression is used.|
+-----------------------------------+--------------------------------------------+
| and / &&                          | Binary "and" operator                      |
+-----------------------------------+--------------------------------------------+
| or / ||                           | Binary "or" operator                       |
+-----------------------------------+--------------------------------------------+
| ==                                | Binary "is equal" operator                 |
+-----------------------------------+--------------------------------------------+
| not / !                           | Negation operator                          |
+-----------------------------------+--------------------------------------------+

Further Resources
-----------------

.. toctree ::
    :hidden:

    /cookbook/creating_your_own_expression_function

- :doc:`Creating Your Own Expression Function </cookbook/creating_your_own_expression_function>`
