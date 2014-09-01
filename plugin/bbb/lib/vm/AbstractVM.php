<?php
/* For licensing terms, see /license.txt */

/**
 * Class AbstractVM
 */
abstract class AbstractVM
{
    public $name;
    public $host;
    public $user;
    public $vmId;
    public $vmMinSize;
    public $vmMaxSize;
    public $apiKey;
    public $vmClientId;
    public $messages = array();
    protected $connector;

    /**
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->name = $settings['name'];
        $this->host = $settings['host'];
        $this->user = $settings['user'];
        $this->apiKey = $settings['api_key'];
        $this->vmId = $settings['vm_id'];
        $this->vmMinSize = $settings['vm_min_size_id'];
        $this->vmMaxSize = $settings['vm_max_size_id'];
        $this->vmClientId = $settings['vm_client_id'];
    }

    /**
     * @param string $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * @return string
     */
    public function getMessageToString()
    {
        return implode(PHP_EOL, $this->messages);
    }
}
