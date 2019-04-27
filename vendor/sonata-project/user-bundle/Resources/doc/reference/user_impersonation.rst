.. index::
    single: User Impersonation

User Impersonation
==================

To enable the user impersonation links you must first enable the impersonation feature in your
firewall. Once you have enabled the feature, you will need to ensure that the user that you wish
to role switch from has the ``ROLE_ALLOWED_TO_SWITCH`` role.

.. code-block:: yaml

    role_hierarchy:
        ...
        ROLE_SUPER_ADMIN: [ROLE_SONATA_ADMIN, ROLE_ALLOWED_TO_SWITCH]

    firewalls:
        main:
            ...
        admin:
            ...
            switch_user: true

Please note that sessions are not shared by default over different firewalls (for example, if you
are trying to switch to a user who is authenticated on another firewall, such as ``main`` given in
the example above).  To enable this feature please refer to the
`Symfony Documentation <http://symfony.com/doc/current/reference/configuration/security.html#reference-security-firewall-context>`_.


Then is it simply a case of providing the route name to redirect to once the user has been impersonated
in the ``SonataUserBundle`` configuration:

.. code-block:: yaml

    sonata_user:
        ...
        impersonating:
            route: sonata_admin_dashboard

Here we have used the admin dashboard route, but you can supply any named route in your application.