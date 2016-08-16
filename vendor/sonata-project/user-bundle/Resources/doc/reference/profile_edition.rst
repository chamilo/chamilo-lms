.. index::
    single: Profile
    single: Configuration

Profile Edition
===============

The ``FOSUserBundle`` already has a form to edit a `profile`, the form allows to edit login and password information. However the ``SonataUserBundle`` introduces new
fields inside the user table. In order to have a clear separation between authentication information and profile information, a specific form has been created.

In ``SonataUserBundle``, the `profile` term is related to user information and the `authentication` term is related to the user's login and password. This separation
is also useful if the credential system is not the default one provided by the solution but still want to store user's metadata.

So if you have configured a specific `profile` form inside the ``FOSUserBundle``, you actually edit the authentication form inside the ``SonataUserBundle`` (if you enable the feature).

This is also the reason why the configuration between ``FOSUserBundle`` and ``SonataUserBundle`` might look the same, however the final purpose is not the same.

Sonata Profile Configuration
----------------------------

You are not forced to use the current configuration as everything can be done in the ``FOSUserBundle`` from the original `profile` form. Now, if you like to quickly build a user profile with more fields
than the default one, please continue reading ...

First, the `fos_user` section need to be altered to set the valid `Authentication` group.

.. code-block:: yaml

    fos_user:
        db_driver:        orm # can be orm or odm
        firewall_name:    main
        user_class:       Application\Sonata\UserBundle\Entity\User

        group:
            group_class:  Application\Sonata\UserBundle\Entity\Group

        profile:  # Authentication Form
            form:
                type:               fos_user_profile
                handler:            fos_user.profile.form.handler.default
                name:               fos_user_profile_form
                validation_groups:  [Authentication] # Please note : this is not the default value

Next, you need to configure the `profile` section of `sonata_user`:

.. code-block:: yaml

    sonata_user:
        security_acl:     false
        class:
            user:         Application\Sonata\UserBundle\Entity\User
            group:        Application\Sonata\UserBundle\Entity\Group

        profile:  # Profile Form (firstname, lastname, etc ...)
            form:
                type:               sonata_user_profile
                handler:            sonata.user.profile.form.handler.default
                name:               sonata_user_profile_form
                validation_groups:  [Profile]

And finally, just change the default `profile` routing. Actually it is the only configuration you need to define to make it work.

.. code-block:: yaml

    # replace these 3 lines :
    fos_user_profile:
        resource: "@FOSUserBundle/Resources/config/routing/profile.xml"
        prefix: /profile

    # by those lines :
    sonata_user_profile:
        resource: "@SonataUserBundle/Resources/config/routing/sonata_profile_1.xml"
        prefix: /profile

Actions
-------

The ``SonataUserBundle`` comes with 3 profiles actions:

 - `show`: show the current user; this is actually a user dashboard to give the user a portal. The dashboard works as `SonataAdminBundle's dashboard <https://sonata-project.org/bundles/admin/master/doc/reference/dashboard.html>`_.
 - `edit profile`: edit the profile information (lastname, firstname, etc ...)
 - `edit authentication`: edit the profile authentication information (login, password)

