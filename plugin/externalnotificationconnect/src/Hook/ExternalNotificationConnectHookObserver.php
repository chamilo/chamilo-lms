<?php

/* For licensing terms, see /license.txt */

abstract class ExternalNotificationConnectHookObserver extends HookObserver
{
    /**
     * @var ExternalNotificationConnectPlugin
     */
    protected $plugin;

    protected function __construct()
    {
        parent::__construct(
            'plugin/externalnotificationconnect/src/ExternalNotificationConnectPlugin.php',
            'externalnotificationconnect'
        );

        $this->plugin = ExternalNotificationConnectPlugin::create();
    }
}
