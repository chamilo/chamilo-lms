<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

/**
 * Class CourseMeetingList. A List of course meetings.
 *
 * @see CourseMeetingListItem
 *
 * @package Chamilo\PluginBundle\Zoom
 */
class CourseMeetingList extends API\MeetingList
{
    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return CourseMeetingListItem::class;
        }

        return parent::itemClass($propertyName);
    }
}
