Configuration
=============

Introduction
------------
By default, you can only use the provided :doc:`annotations <annotations>` on your
non-service controllers; no other directories are scanned.

However, if you also would like to use annotations to configure your regular services, 
you can configure more locations as demonstrated below.

Configuration Locations
-----------------------
If you would like to configure services in a bundle of yours via annotations, or
have some services outside of any bundles structure such as in your ``src/`` directory,
you can make use of the following configuration options, so that the bundle will pick
them up, and add them to your dependency injection container:

.. configuration-block ::

    .. code-block :: yaml
    
        jms_di_extra:
            locations:
                all_bundles: false
                bundles: [FooBundle, AcmeBlogBundle]
                directories: ["%kernel.root_dir%/../src"]

    .. code-block :: xml
    
        <jms-di-extra>
            <locations all-bundles="false">
                <bundle>FooBundle</bundle>
                <bundle>AcmeBlogBundle</bundle>
                
                <directory>%kernel.root_dir%/../src</directory>
            </locations>
        </jms-di-extra>

.. tip ::

    For optimal development performance (in production there is no difference either way), 
    it is recommended to explicitly configure the directories which should be scanned for 
    service classes, and not rely on the ``all_bundles`` configuration option.

Automatic Controller Injections
-------------------------------
This bundle allows you to configure injection for certain properties, and methods
of controllers automatically. This is most useful for commonly needed services 
which then do not need to be annotated explicitly anymore.

.. configuration-block ::

    .. code-block :: yaml

        jms_di_extra:
            automatic_controller_injections:
                properties:
                    request: "@request"
                    router: "@router"
                    
                method_calls:
                    setRouter: ["@router"]
                    
    .. code-block :: xml
    
        <jms-di-extra>
            <automatic-controller-injections>
                <property name="request">@request</property>
                <property name="router">@router</property>
                
                <method-call name="setRouter">@router</method-call>
            </automatic-controller-injections>
        </jms-di-extra>                 

If your controller has any of the above properties, or methods, then you do not need
to add an @Inject annotation anymore, but we will automatically inject the configured
services for you. However, if you do declare an @Inject annotation it will automatically
overwrite whatever you have configured in the above section.
