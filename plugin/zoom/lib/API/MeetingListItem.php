<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingListItem. Item of a list of meetings.
 *
 * @see MeetingList
 */
class MeetingListItem
{
    use BaseMeetingTrait;
    use JsonDeserializableTrait;

    /** @var string unique meeting instance ID */
    public $uuid;

    /** @var string meeting number */
    public $id;

    /** @var string host Zoom user id */
    public $host_id;

    /** @var string */
    public $created_at;

    /** @var string */
    public $join_url;

    /** @var string truncated to 250 characters */
    // public $agenda;

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        throw new Exception("no such array property $propertyName");
    }
}
