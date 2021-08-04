<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

/**
 * Class MeetingInfo
 * Used to define MeetingInfoGet
 * Does not seem to be used directly.
 */
class MeetingInfo extends Meeting
{
    /** @var string */
    public $created_at;

    /** @var string, allows host to start the meeting as the host (without password) - not to be shared */
    public $start_url;

    /** @var string, for participants to join the meeting - to share with users to invite */
    public $join_url;

    /** @var string undocumented */
    public $registration_url;

    /** @var string H.323/SIP room system password */
    public $h323_password;

    /** @var int Personal Meeting Id. Only used for scheduled meetings and recurring meetings with no fixed time */
    public $pmi;

    /** @var object[] */
    public $occurrences;
}
