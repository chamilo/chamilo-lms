<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class CreatedRegistration
{
    use JsonDeserializable;

    /** @var int meeting ID */
    public $id;

    /** @var string Unique URL for this registrant to join the meeting.
     * This URL should only be shared with the registrant for whom the API request was made.
     * If the meeting was created with manual approval type (1), the join URL will not be returned in the response.
     */
    public $join_url;

    /** @var Unique identifier of the registrant */
    public $registrant_id;

    /** @var string The start time for the meeting. */
    public $start_time;

    /** @var string Topic of the meeting. */
    public $topic;
}
