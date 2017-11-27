<?php
/* For licensing terms, see /license.txt */

/**
 * Events' configuration
 * @deprecated to be removed in 2.x
 * Used to configure each event and to link them to functions the event'll fire.
 * The flow is like the following :
 * 1. somewhere in the application an event is fired
 * 2. that event is intercepted by the switch EventsDispatcher
 * 3. that switch will go all over the "actions" in the event_config initialized beneath us according to the event
 * 4. that switch will see if the function actually exists (if not, we get dont do anything)
 * 5. then it will see if a filter for that function exists (if it does, the filter is executed)
 * 6. if the filter says it's ok, the function linked to the event is executed
 * 7. and that function will actually call the truly interesting function with the good require_once 
 */
global $event_config;

$event_config = array(
    'portal_homepage_edited' => array( // key for "user registration" event
        'actions' => array( // we link this event to a bunch of functions that will be triggered when the event is fired
            'event_send_mail' // don't forget to actually write this function in the events.lib.php file
        ),
        'self_sent' => false, // this key states that we can't add user to this event through the admin panel
        'name_lang_var' => get_lang('PortalHomepageEdited'),
        'desc_lang_var' => get_lang('PortalHomepageEdited'),
        'available_keyvars' => array (// keys used for the mail template
            'url'           => 'portal',
            'sitename'      => 'sitename',
            'firstname'     => 'firstname',
            'lastname'      => 'lastname',
            'username'      => 'username',
            'usermail'      => 'usermail',
            'password'      => 'password',
            'user_lang'     => 'language',
            'admin_name'    => 'administrator_name',
            'admin_surname' => 'administrator_surname',
            'admin_phone'   => 'administrator_phone',
            'admin_email'   => 'administrator_email',
        )
    ),
    'user_registration' => array( // key for "user registration" event
        'actions' => array( // we link this event to a bunch of functions that will be triggered when the event is fired
            'event_send_mail' // don't forget to actually write this function in the events.lib.php file
        ),
        'self_sent' => true, // this key states that we can't add user to this event through the admin panel
        'name_lang_var' => get_lang('UserRegistrationTitle'),
        'desc_lang_var' => get_lang('UserRegistrationComment'),
        'available_keyvars' => array (// keys used for the mail template
            'url'           => 'portal',
            'sitename'      => 'sitename',
            'firstname'     => 'firstname',
            'lastname'      => 'lastname',
            'username'      => 'username',
            'usermail'      => 'usermail',
            'password'      => 'password',
            'user_lang'     => 'language',
            'admin_name'    => 'administrator_name',
            'admin_surname' => 'administrator_surname',
            'admin_phone'   => 'administrator_phone',
            'admin_email'   => 'administrator_email',
        )
    ),
);


@include 'events.conf.local.php';
