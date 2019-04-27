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

/**
 * DomainsActions class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DomainsActions
{
    /**
     * Available actions.
     *
     * @var string
     */
    const ACTION_ADD     = 'new';
    const ACTION_DESTROY = 'destroy';
    const ACTION_RECORDS = 'records';
}
