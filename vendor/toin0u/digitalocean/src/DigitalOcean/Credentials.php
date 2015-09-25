<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean;

/**
 * Credentials class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Credentials
{
    /**
     * The client ID.
     *
     * @var string
     */
    private $clientId;

    /**
     * The API key.
     *
     * @var string
     */
    private $apiKey;


    /**
     * Constructor.
     *
     * @param string $clientId The cliend ID.
     * @param string $apiKey   The API key.
     */
    public function __construct($clientId, $apiKey)
    {
        $this->clientId = $clientId;
        $this->apiKey   = $apiKey;
    }

    /**
     * Returns the Client ID.
     *
     * @return string The client ID.
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Returns the API Key.
     *
     * @return string The API Key.
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}
