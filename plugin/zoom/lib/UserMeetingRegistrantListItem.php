<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

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
