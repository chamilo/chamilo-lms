<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Trait BaseMeetingTrait.
 * Common meeting properties definitions.
 */
trait BaseMeetingTrait
{
    /** @var string */
    public $topic;

    /** @var int */
    public $type;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" for GMT, same without 'Z' for local time (as set on zoom account) */
    public $start_time;

    /** @var int in minutes, for scheduled meetings only */
    public $duration;

    /** @var string the timezone for start_time */
    public $timezone;

    /** @var string description */
    public $agenda;
    /** @var string description */
    public $host_email;

    /**
     * @throws Exception
     */
    public function update(): void
    {
    }
}
