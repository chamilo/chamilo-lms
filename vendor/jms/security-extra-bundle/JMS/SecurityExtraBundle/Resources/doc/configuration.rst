Configuration
-------------

Below, you find the default configuration:

.. configuration-block ::

    .. code-block :: yaml
    
        # app/config/config.yml
        jms_security_extra:
            # Whether you want to secure all services (true), or only secure specific
            # services (false); see also below 
            secure_all_services: false
            
            # Enabling this setting will add an additional special attribute "ROLE_IDDQD".
            # Anybody with this attribute will effectively bypass all security checks.
            enable_iddqd_attribute: false        
            
            # Enables expression language
            expressions: false
    
            # Allows you to disable some, or all built-in voters
            voters:
                disable_authenticated: false
                disable_role:          false
                disable_acl:           false
                
            # Allows you to specify access control rules for specific methods, such
            # as controller actions
            method_access_control: { }
    
            util:
                secure_random:
                    connection: # the doctrine connection name
                    table_name: seed_table
                    seed_provider: # service id of your own seed provider implementation

    .. code-block :: xml
    
        <jms-security-extra 
            secure-all-services="false"
            enable-iddqd-attribute="false"
            expressions="false">
        
            <voters disable-authenticated="false"
                    disable-role="false"
                    disable-acl="false" />
                    
            <util>
                <secure-random
                    connection="default"
                    table-name="seed-table"
                    seed-provider="some-service-id" />
            </util>
        </jms-security-extra>