.. index::
    single: Advanced configuration
    single: Options

Advanced Configuration
======================

Full configuration options:

.. code-block:: yaml

    fos_user:
        db_driver:        orm # can be orm or mongodb (support is also available within FOSUser for couchdb, propel but none is given for SonataUserBundle)
        firewall_name:    main
        user_class:       Application\Sonata\UserBundle\Entity\User

        group:
            group_class:  Application\Sonata\UserBundle\Entity\Group

        profile:
            # Authentication Form
            form:
                type:               fos_user_profile
                handler:            fos_user.profile.form.handler.default
                name:               fos_user_profile_form
                validation_groups:  [Authentication] # Please note : this is not the default value

    sonata_user:
        security_acl:           false

	manager_type: orm # Can be orm or mongodb

        table:
            user_group: "my_custom_user_group_association_table_name"

        impersonating:
            route:                page_slug
            parameters:           { path: / }

        class:                  # Entity Classes
            user:               Application\Sonata\UserBundle\Entity\User
            group:              Application\Sonata\UserBundle\Entity\Group

        admin:                  # Admin Classes
            user:
                class:          Sonata\UserBundle\Admin\Entity\UserAdmin
                controller:     SonataAdminBundle:CRUD
                translation:    SonataUserBundle

            group:
                class:          Sonata\UserBundle\Admin\Entity\GroupAdmin
                controller:     SonataAdminBundle:CRUD
                translation:    SonataUserBundle

        profile:
            default_avatar: 'bundles/sonatauser/default_avatar.png' # Default avatar displayed if user doesn't have one
            # As in SonataAdminBundle's dashboard
            dashboard:
                groups:

                    # Prototype
                    id:
                        label:                ~
                        label_catalogue:      ~
                        items:                []
                        item_adds:            []
                        roles:                []
                blocks:
                    type:                 ~
                    settings:

                        # Prototype
                        id:                   []
                    position:             right
            register:
                # You may customize the registration forms over here
                form:
                    type:                 sonata_user_registration
                    handler:              sonata.user.registration.form.handler.default
                    name:                 sonata_user_registration_form
                    validation_groups:

                        # Defaults:
                        - Registration
                        - Default
                # This allows you to specify where you want your user redirected once he activated his account
                confirm:
                    redirect:
                        # Set it to false to disable redirection
                        route: 'sonata_user_profile_show'
                        route_parameters: ~

            # Customize user portal menu by setting links
            menu:
                -
                    route: 'sonata_user_profile_edit'
                    label: 'link_edit_profile'
                    domain: 'SonataUserBundle'
                -
                    route: 'sonata_user_profile_edit_authentication'
                    label: 'link_edit_authentication'
                    domain: 'SonataUserBundle'

            # Profile Form (firstname, lastname, etc ...)
            form:
                type:               sonata_user_profile
                handler:            sonata.user.profile.form.handler.default
                name:               sonata_user_profile_form
                validation_groups:  [Profile]

    # override FOSUser default serialization
    jms_serializer:
        metadata:
            directories:
                -
                    path: "%kernel.root_dir%/../vendor/sonata-project/user-bundle/Sonata/UserBundle/Resources/config/serializer/FOSUserBundle"
                    namespace_prefix: 'FOS\UserBundle'

    # Enable Doctrine to map the provided entities
    doctrine:
        orm:
            entity_managers:
                default:
                    mappings:
                        FOSUserBundle: ~
                        ApplicationSonataUserBundle: ~
                        SonataUserBundle: ~
