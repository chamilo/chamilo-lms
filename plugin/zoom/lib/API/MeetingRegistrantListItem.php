<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

/**
 * Class MeetingRegistrantListItem. Item in a list of meeting registrants.
 *
 * @see MeetingRegistrantList
 */
class MeetingRegistrantListItem extends MeetingRegistrant
{
    /** @var string Registrant ID. */
    public $id;

    /** @var string The status of the registrant's registration.
     * `approved`: User has been successfully approved for the webinar.
     * `pending`:  The registration is still pending.
     * `denied`: User has been denied from joining the webinar.
     */
    public $status;

    /** @var string The time at which the registrant registered. */
    public $create_time;

    /** @var string The URL using which an approved registrant can join the webinar. */
    public $join_url;
}
