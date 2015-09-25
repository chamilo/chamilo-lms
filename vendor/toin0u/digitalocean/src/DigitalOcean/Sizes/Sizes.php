<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Sizes;

use DigitalOcean\Credentials;
use DigitalOcean\AbstractDigitalOcean;
use HttpAdapter\HttpAdapterInterface;

/**
 * Sizes class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Sizes extends AbstractDigitalOcean
{
    /**
     * Sizes API name.
     *
     * @var string
     */
    const SIZES = 'sizes';


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        parent::__construct($credentials, $adapter);

        $this->apiUrl = sprintf("%s/%s", $this->apiUrl, self::SIZES);
    }

    /**
     * Returns all the available sizes that can be used to create a droplet.
     *
     * @return StdClass
     */
    public function getAll()
    {
        return $this->processQuery($this->buildQuery());
    }
}
