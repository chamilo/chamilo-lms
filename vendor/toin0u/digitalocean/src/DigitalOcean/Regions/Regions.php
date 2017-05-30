<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Regions;

use DigitalOcean\Credentials;
use DigitalOcean\AbstractDigitalOcean;
use HttpAdapter\HttpAdapterInterface;

/**
 * Regions class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Regions extends AbstractDigitalOcean
{
    /**
     * Regions API name.
     *
     * @var string
     */
    const REGIONS = 'regions';


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        parent::__construct($credentials, $adapter);

        $this->apiUrl = sprintf("%s/%s", $this->apiUrl, self::REGIONS);
    }

    /**
     * Returns all the available regions within the Digital Ocean cloud.
     *
     * @return StdClass
     */
    public function getAll()
    {
        return $this->processQuery($this->buildQuery());
    }
}
