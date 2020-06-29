<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

/**
 * Class UserMeetingRegistrantListItem. An item of a user registrant list.
 *
 * @see UserMeetingRegistrantList
 *
 * @package Chamilo\PluginBundle\Zoom
 */
class UserMeetingRegistrantListItem extends API\MeetingRegistrantListItem
{
    use UserMeetingRegistrantTrait;

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        parent::initializeExtraProperties();
        $this->decodeAndRemoveTag();
        $this->computeFullName();
    }
}
