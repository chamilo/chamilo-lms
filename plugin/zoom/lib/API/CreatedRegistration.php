<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class CreatedRegistration.
 * An instance of this class is returned by the Zoom server upon recording a registrant to a meeting.
 */
class CreatedRegistration
{
    use JsonDeserializableTrait;

    /** @var int meeting ID */
    public $id;

    /** @var string Unique URL for this registrant to join the meeting.
     * This URL should only be shared with the registrant for whom the API request was made.
     * If the meeting was created with manual approval type (1), the join URL will not be returned in the response.
     */
    public $join_url;

    /** @var string Unique identifier of the registrant */
    public $registrant_id;

    /** @var string The start time for the meeting. */
    public $start_time;

    /** @var string Topic of the meeting. */
    public $topic;

    /**
     * {@inheritdoc}
     */
    protected function itemClass($propertyName)
    {
        throw new Exception("no such array property $propertyName");
    }
}
