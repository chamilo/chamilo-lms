<?php

/* For license terms, see /license.txt */

use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureCommand
{
    /**
     * @var AzureActiveDirectory
     */
    protected $plugin;
    /**
     * @var Azure
     */
    protected $provider;

    public function __construct()
    {
        $this->plugin = AzureActiveDirectory::create();
        $this->plugin->get_settings(true);
        $this->provider = $this->plugin->getProviderForApiGraph();
    }
}
