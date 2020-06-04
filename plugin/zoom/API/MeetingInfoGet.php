<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class MeetingInfoGet extends MeetingInfo
{
    /** @var string unique meeting instance ID */
    public $uuid;

    /** @var string meeting number */
    public $id;

    /** @var string host Zoom user id */
    public $host_id;

    /** @var string meeting status, either "waiting", "started" or "finished" */
    public $status;

    /** @var string undocumented */
    public $pstn_password;

    /** @var string Encrypted password for third party endpoints (H323/SIP). */
    public $encrypted_password;
}
