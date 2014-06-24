Method Security Authorization
-----------------------------
Securing methods allows for the most fine-grained access control that Symfony2
has to offer. 

Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured. You can also add metadata for
abstract methods, or interfaces which will then be applied to their concrete 
implementations automatically.

Access Control via DI configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
You can specify access control **expressions** in the DI configuration:

.. code-block :: yaml

    # config.yml
    jms_security_extra:
        method_access_control:
            ':loginAction$': 'isAnonymous()'
            'AcmeFooBundle:.*:deleteAction': 'hasRole("ROLE_ADMIN")'
            '^MyNamespace\MyService::foo$': 'hasPermission(#user, "VIEW")' 

The pattern is a case-sensitive regular expression which is matched against two notations.
The first match is being used.

First, your pattern is matched against the notation for non-service controllers. 
This obviously is only done if your class is actually a controller, e.g. 
``AcmeFooBundle:Add:new`` for a controller named ``AddController`` and a method 
named ``newAction`` in a sub-namespace ``Controller`` in a bundle named ``AcmeFooBundle``. 

Last, your pattern is matched against the concatenation of the class name, and
the method name that is being called, e.g. ``My\Fully\Qualified\ClassName::myMethodName``.

**Note:** If you would like to secure non-service controllers, the 
``JMSDiExtraBundle`` must be installed.

Access Control via Annotations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
If you like to secure a service with annotations, you need to enable annotation
configuration for this service:

.. configuration-block ::

    .. code-block :: yaml
    
        services:
            foo:
                class: Bar
                tags: [ { name: "security.secure_service" } ]

    .. code-block :: xml

        <service id="foo" class="Bar">
            <tag name="security.secure_service"/>
        </service>
        

In case, you like to configure all services via annotations, you can also set
``secure_all_services`` to true. Then, you do not need to add a tag for each 
service.
