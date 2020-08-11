<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Events;

use DigitalOcean\Credentials;
use DigitalOcean\AbstractDigitalOcean;
use HttpAdapter\HttpAdapterInterface;

/**
 * Events class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Events extends AbstractDigitalOcean
{
    /**
     * Events API name.
     *
     * @var string
     */
    const EVENTS = 'events';


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        parent::__construct($credentials, $adapter);

        $this->apiUrl = sprintf("%s/%s", $this->apiUrl, self::EVENTS);
    }

    /**
     * Reports on the progress of an event by providing the percentage of completion.
     *
     * @param integer $event The id or the event.
     *
     * @return StdClass
     */
    public function show($event)
    {
        return $this->processQuery($this->buildQuery($event));
    }
}
