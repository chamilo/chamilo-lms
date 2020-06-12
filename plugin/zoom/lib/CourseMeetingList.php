<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class CourseMeetingList extends API\MeetingList
{
    /**
     * {@inheritdoc}
     */
    protected function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return CourseMeetingListItem::class;
        }

        return parent::itemClass($propertyName);
    }
}
