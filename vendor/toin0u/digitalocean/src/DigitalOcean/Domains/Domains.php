<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Domains;

use DigitalOcean\Credentials;
use DigitalOcean\Domains\DomainsActions;
use DigitalOcean\Domains\RecordsActions;
use DigitalOcean\AbstractDigitalOcean;
use HttpAdapter\HttpAdapterInterface;

/**
 * Domains class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Domains extends AbstractDigitalOcean
{
    /**
     * Domains API name.
     *
     * @var string
     */
    const DOMAINS = 'domains';


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        parent::__construct($credentials, $adapter);

        $this->apiUrl = sprintf("%s/%s", $this->apiUrl, self::DOMAINS);
    }

    /**
     * Returns all of your current domains.
     *
     * @return StdClass
     */
    public function getAll()
    {
        return $this->processQuery($this->buildQuery());
    }

    /**
     * Shows a specific domain.
     *
     * @param integer|string $domain The id or the name of the domain.
     *
     * @return StdClass
     */
    public function show($domain)
    {
        return $this->processQuery($this->buildQuery($domain));
    }

    /**
     * Adds a new domain with an A record for the specified IP address.
     * The array requires name and ip_address keys.
     *
     * @param array $parameters An array of parameters.
     *
     * @return StdClass
     *
     * @throws \InvalidArgumentException
     */
    public function add(array $parameters)
    {
        if (!array_key_exists('name', $parameters) || !is_string($parameters['name'])) {
            throw new \InvalidArgumentException('You need to provide the name of the domain.');
        }

        if (!array_key_exists('ip_address', $parameters) || !is_string($parameters['ip_address'])) {
            throw new \InvalidArgumentException('You need to provide the IP address for the domain\'s initial A record.');
        }

        return $this->processQuery($this->buildQuery(null, DomainsActions::ACTION_ADD, $parameters));
    }

    /**
     * Deletes the specified domain.
     *
     * @param integer|string $domain The id or the name of the domain.
     *
     * @return StdClass
     */
    public function destroy($domain)
    {
        return $this->processQuery($this->buildQuery($domain, DomainsActions::ACTION_DESTROY));
    }

    /**
     * Builds the records API url according to the parameters.
     *
     * @param integer|string $domain     The id or the name of the domain.
     * @param integer        $recordId   The Id of the record to work with (optional).
     * @param string         $action     The action to perform (optional).
     * @param array          $parameters An array of parameters (optional).
     *
     * @return string The built API url.
     */
    protected function buildRecordsQuery($domain, $id = null, $action = null, array $parameters = array())
    {
        $parameters = http_build_query(array_merge($parameters, $this->credentials));

        $query = sprintf("%s/%s/%s", $this->apiUrl, $domain, DomainsActions::ACTION_RECORDS);
        $query = $id ? sprintf("%s/%s", $query, $id) : $query;
        $query = $action ? sprintf("%s/%s/?%s", $query, $action, $parameters) : sprintf("%s/?%s", $query, $parameters);

        return $query;
    }

    /**
     * Check submitted parameters.
     *
     * The array requires record_type and data keys:
     * - record_type can be only 'A', 'CNAME', 'NS', 'TXT', 'MX' or 'SRV'.
     * - data is a string, the value of the record.
     *
     * Special cases:
     * - name is a required string if the record_type is 'A', 'CNAME', 'TXT' or 'SRV'.
     * - priority is an required integer if the record_type is 'SRV' or 'MX'.
     * - port is an required integer if the record_type is 'SRV'.
     * - weight is an required integer if the record_type is 'SRV'.
     *
     * @param array $parameters An array of parameters.
     *
     * @throws \InvalidArgumentException
     */
    protected function checkParameters(array $parameters)
    {
        if (!array_key_exists('record_type', $parameters)) {
            throw new \InvalidArgumentException('You need to provide the record_type.');
        }

        if (!in_array($parameters['record_type'], array('A', 'CNAME', 'NS', 'TXT', 'MX', 'SRV'))) {
            throw new \InvalidArgumentException('The record_type can only be A, CNAME, NS, TXT, MX or SRV');
        }

        if (!array_key_exists('data', $parameters) || !is_string($parameters['data'])) {
            throw new \InvalidArgumentException('You need to provide the data value of the record.');
        }

        if (in_array($parameters['record_type'], array('A', 'CNAME', 'TXT', 'SRV'))) {
            if (!array_key_exists('name', $parameters) || !is_string($parameters['name'])) {
                throw new \InvalidArgumentException('You need to provide the name string if the record_type is A, CNAME, TXT or SRV.');
            }
        }

        if (in_array($parameters['record_type'], array('SRV', 'MX'))) {
            if (!array_key_exists('priority', $parameters) || !is_int($parameters['priority'])) {
                throw new \InvalidArgumentException('You need to provide the priority integer if the record_type is SRV or MX.');
            }
        }

        if ('SRV' === $parameters['record_type']) {
            if (!array_key_exists('port', $parameters) || !is_int($parameters['port'])) {
                throw new \InvalidArgumentException('You need to provide the port integer if the record_type is SRV.');
            }
        }

        if ('SRV' === $parameters['record_type']) {
            if (!array_key_exists('weight', $parameters) || !is_int($parameters['weight'])) {
                throw new \InvalidArgumentException('You need to provide the weight integer if the record_type is SRV.');
            }
        }
    }

    /**
     * Returns all of your current domain records.
     *
     * @param integer|string $domain The id or the name of the domain.
     *
     * @return StdClass
     */
    public function getRecords($domain)
    {
        return $this->processQuery($this->buildRecordsQuery($domain));
    }

    /**
     * Adds a new record to a specific domain.
     *
     * @param integer|string $domain     The id or the name of the domain.
     * @param array          $parameters An array of parameters.
     *
     * @return StdClass
     */
    public function newRecord($domain, array $parameters)
    {
        $this->checkParameters($parameters);

        return $this->processQuery(
            $this->buildRecordsQuery($domain, null, RecordsActions::ACTION_ADD, $parameters)
        );
    }

    /**
     * Returns the specified domain record.
     *
     * @param integer|string $domain   The id or the name of the domain.
     * @param integer        $recordId The id of the record.
     *
     * @return StdClass
     */
    public function getRecord($domain, $recordId)
    {
        return $this->processQuery($this->buildRecordsQuery($domain, $recordId));
    }

    /**
     * Edits a record to a specific domain.
     *
     * @param integer|string $domain     The id or the name of the domain.
     * @param integer        $recordId   The id of the record.
     * @param array          $parameters An array of parameters.
     *
     * @return StdClass
     */
    public function editRecord($domain, $recordId, array $parameters)
    {
        $this->checkParameters($parameters);

        return $this->processQuery(
            $this->buildRecordsQuery($domain, $recordId, RecordsActions::ACTION_EDIT, $parameters)
        );
    }

    /**
     * Deletes the specified domain record.
     *
     * @param integer|string $domain   The id or the name of the domain.
     * @param integer        $recordId The id of the record.
     *
     * @return StdClass
     */
    public function destroyRecord($domain, $recordId)
    {
        return $this->processQuery($this->buildRecordsQuery($domain, $recordId, RecordsActions::ACTION_DESTROY));
    }
}
