Installation
============
Prerequisites
-------------
PHP 5.3 and Symfony 2 are needed to make this bundle work ; there are also some
Sonata dependencies that need to be installed and configured beforehand :

    - `SonataAdminBundle <http://sonata-project.org/bundles/admin>`_
    - `SonataEasyExtendsBundle <http://sonata-project.org/bundles/easy-extends>`_

You will need to install those in their 2.0 branches (or master if they don't
have a similar branch). Follow also their configuration step ; you will find
everything you need in their installation chapter.

.. note::
    If a dependency is already installed somewhere in your project or in
    another dependency, you won't need to install it again.

Enable the Bundle
-----------------

.. code-block:: ini

    php composer require sonata-project/user-bundle --no-update
    php composer require sonata-project/doctrine-orm-admin-bundle  --no-update # optional
    php composer update

Next, be sure to enable the bundles in your autoload.php and AppKernel.php
files:

.. code-block:: php

    <?php

    // app/appkernel.php
    public function registerbundles()
    {
        return array(
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            // ...
            // You have 2 options to initialize the SonataUserBundle in your AppKernel,
            // you can select which bundle SonataUserBundle extends
            // extend the ``FOSUserBundle``
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            // OR
            // the bundle will NOT extend ``FOSUserBundle``
            new Sonata\UserBundle\SonataUserBundle(),
            // ...
        );
    }

.. note::
    If you already have installed a Sonata dependency, you may ignore the step
    on the modification of the ``autoload.php`` file.

Configuration
-------------
When using ACL, the UserBundle can prevent ``normal`` user to change settings
of ``super-admin`` users, to enable this add to the configuration:

.. code-block:: yaml

    # app/config/config.yml
    sonata_user:
        security_acl: true
        manager_type: orm # can be orm or mongodb

    sonata_block:
        blocks:
            #...
            sonata.user.block.menu:    # used to display the menu in profile pages
            sonata.user.block.account: # used to display menu option (login option)

    # app/config/security.yml
    security:
        # [...]
        acl:
            connection: default

Doctrine Configuration
~~~~~~~~~~~~~~~~~~~~~~
Then add these bundles in the config mapping definition (or enable `auto_mapping <http://symfony.com/doc/2.0/reference/configuration/doctrine.html#configuration-overview>`_):

.. code-block:: yaml

    # app/config/config.yml

    fos_user:
        db_driver:      orm # can be orm or odm
        firewall_name:  main
        user_class:     Application\Sonata\UserBundle\Entity\User


        group:
            group_class:   Application\Sonata\UserBundle\Entity\Group
            group_manager: sonata.user.orm.group_manager                    # If you're using doctrine orm

        service:
            user_manager: sonata.user.orm.user_manager                      # If you're using doctrine orm

    doctrine:
        orm:
            entity_managers:
                default:
                    mappings:
                        ApplicationSonataUserBundle: ~
                        SonataUserBundle: ~

        dbal:
            types:
                json: Sonata\Doctrine\Types\JsonType



Use custom SonataUser controllers and templates instead of FOSUser ones
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you wish to use custom SonataUser templates and controllers instead of FOSUser ones, you will have to update your ``routing.yml`` file as follows:

Replace

.. code-block:: yaml

    fos_user_security:
        resource: "@FOSUserBundle/Resources/config/routing/security.xml"

    fos_user_resetting:
        resource: "@FOSUserBundle/Resources/config/routing/resetting.xml"
        prefix: /resetting

    fos_user_profile:
        resource: "@FOSUserBundle/Resources/config/routing/profile.xml"
        prefix: /profile

    fos_user_register:
        resource: "@FOSUserBundle/Resources/config/routing/registration.xml"
        prefix: /register

    fos_user_change_password:
        resource: "@FOSUserBundle/Resources/config/routing/change_password.xml"
        prefix: /profile

With:

.. code-block:: yaml

    sonata_user_security:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_security_1.xml"

    sonata_user_resetting:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_resetting_1.xml"
        prefix: /resetting

    sonata_user_profile:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_profile_1.xml"
        prefix: /profile

    sonata_user_register:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_registration_1.xml"
        prefix: /register

    sonata_user_change_password:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_change_password_1.xml"
        prefix: /profile


Integrating the bundle into the Sonata Admin Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Add the related security routing information

.. code-block:: yaml

    sonata_user:
        resource: '@SonataUserBundle/Resources/config/routing/admin_security.xml'
        prefix: /admin

Then add a new custom firewall handlers for the admin

.. code-block:: yaml

    security:
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_SONATA_ADMIN]
            ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
            SONATA:
                - ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT  # if you are using acl then this line must be commented

        providers:
            fos_userbundle:
                id: fos_user.user_manager

        firewalls:
            # Disabling the security for the web debug toolbar, the profiler and Assetic.
            dev:
                pattern:  ^/(_(profiler|wdt)|css|images|js)/
                security: false

            # -> custom firewall for the admin area of the URL
            admin:
                pattern:            /admin(.*)
                context:            user
                form_login:
                    provider:       fos_userbundle
                    login_path:     /admin/login
                    use_forward:    false
                    check_path:     /admin/login_check
                    failure_path:   null
                logout:
                    path:           /admin/logout
                anonymous:          true

            # -> end custom configuration

            # defaut login area for standard users

            # This firewall is used to handle the public login area
            # This part is handled by the FOS User Bundle
            main:
                pattern:             .*
                context:             user
                form_login:
                    provider:       fos_userbundle
                    login_path:     /login
                    use_forward:    false
                    check_path:     /login_check
                    failure_path:   null
                logout:             true
                anonymous:          true

The last part is to define 3 new access control rules :

.. code-block:: yaml

    security:
        access_control:
            # URL of FOSUserBundle which need to be available to anonymous users
            - { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }

            # Admin login page needs to be access without credential
            - { path: ^/admin/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/logout$, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/admin/login_check$, role: IS_AUTHENTICATED_ANONYMOUSLY }

            # Secured part of the site
            # This config requires being logged for the whole site and having the admin role for the admin part.
            # Change these rules to adapt them to your needs
            - { path: ^/admin/, role: [ROLE_ADMIN, ROLE_SONATA_ADMIN] }
            - { path: ^/.*, role: IS_AUTHENTICATED_ANONYMOUSLY }


Using the roles
---------------

Each admin has its own roles, use the user form to assign them to other users.
The available roles to assign to others are limited to the roles available to
the user editing the form.

Extending the Bundle
--------------------
At this point, the bundle is functionnal, but not quite ready yet. You need to
generate the correct entities for the media::

    php app/console sonata:easy-extends:generate SonataUserBundle

If you specify no parameter, the files are generated in app/Application/Sonata...
but you can specify the path with ``--dest=src``

.. note::

    The command will generate domain objects in an ``Application`` namespace.
    So you can point entities' associations to a global and common namespace.
    This will make Entities sharing easier as your models will allow to
    point to a global namespace. For instance the user will be
    ``Application\Sonata\UserBundle\Entity\User``.

Now, add the new `Application` Bundle into the kernel:

.. code-block:: php

    <?php

    // AppKernel.php
    class AppKernel {
        public function registerbundles()
        {
            return array(
                // Application Bundles
                // ...
                new Application\Sonata\UserBundle\ApplicationSonataUserBundle(),
                // ...

            )
        }
    }
