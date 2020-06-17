<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class UserMeetingRegistrantList extends API\MeetingRegistrantList
{
    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return UserMeetingRegistrantListItem::class;
        }

        return parent::itemClass($propertyName);
    }
}
