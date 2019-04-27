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

use HttpAdapter\HttpAdapterInterface;

/**
 * DigitalOcean abstract class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
abstract class AbstractDigitalOcean
{
    /**
     * The url of the API endpoint.
     *
     * @var string
     */
    const ENDPOINT_URL = 'https://api.digitalocean.com';


    /**
     * The credentials.
     *
     * @var array
     */
    protected $credentials;

    /**
     * The Id of the element to work with.
     *
     * @var integer
     */
    protected $id;

    /**
     * The API url.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * The adapter to use.
     *
     * @var HttpAdapterInterface
     */
    protected $adapter;


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        $this->credentials = array(
            'client_id' => $credentials->getClientId(),
            'api_key'   => $credentials->getApiKey(),
        );
        $this->adapter = $adapter;
        $this->apiUrl  = self::ENDPOINT_URL;
    }

    /**
     * Builds the API url according to the parameters.
     *
     * @param integer $id         The Id of the element to work with (optional).
     * @param string  $action     The action to perform (optional).
     * @param array   $parameters An array of parameters (optional).
     *
     * @return string The built API url.
     */
    protected function buildQuery($id = null, $action = null, array $parameters = array())
    {
        $parameters = http_build_query(array_merge($parameters, $this->credentials));

        $query = $id ? sprintf("%s/%s", $this->apiUrl, $id) : $this->apiUrl;
        $query = $action ? sprintf("%s/%s/?%s", $query, $action, $parameters) : sprintf("%s/?%s", $query, $parameters);

        return $query;
    }

    /**
     * Processes the query.
     *
     * @param string $query The query to process.
     *
     * @return StdClass
     *
     * @throws \RuntimeException
     */
    protected function processQuery($query)
    {
        if (null === $processed = json_decode($this->adapter->getContent($query))) {
            throw new \RuntimeException(sprintf("Impossible to process this query: %s", $query));
        }

        if ('ERROR' === $processed->status) {
            // it looks that the API does still have the old error object structure.
            if (isset($processed->error_message)) {
                $errorMessage = $processed->error_message;
            }

            if (isset($processed->message)) {
                $errorMessage = $processed->message;
            }

            throw new \RuntimeException(sprintf("%s: %s", $errorMessage, $query));
        }

        return $processed;
    }
}
