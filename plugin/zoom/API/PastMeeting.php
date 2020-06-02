<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class PastMeeting extends Meeting
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

    /** @var string user display name */
    public $user_name;

    /** @var string */
    public $user_email;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" (GMT) */
    public $start_time;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" (GMT) */
    public $end_time;

    /** @var integer in minutes, for scheduled meetings only */
    public $duration;

    /** @var integer sum of meeting minutes from all participants in the meeting. */
    public $total_minutes;

    /** @var integer number of meeting participants */
    public $participants_count;
}
