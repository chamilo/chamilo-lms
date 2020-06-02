<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class MeetingListItem
{
    /** @var string unique meeting instance ID */
    public $uuid;

    /** @var string meeting number */
    public $id;

    /** @var string host Zoom user id */
    public $host_id;

    /** @var string */
    public $topic;

    /** @var integer @see Meeting */
    public $type;

    /** @var string */
    public $start_time;

    /** @var integer in minutes */
    public $duration;

    /** @var string */
    public $timezone;

    /** @var string */
    public $created_at;

    /** @var string */
    public $join_url;

    /** @var string truncated to 250 characters */
    public $agenda;
}
