<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

/**
 * Class CourseMeetingListItem. An item from a course meeting list.
 *
 * @see CourseMeetingList
 *
 * @package Chamilo\PluginBundle\Zoom
 */
class CourseMeetingListItem extends API\MeetingListItem
{
    use CourseMeetingTrait;
    use DisplayableMeetingTrait;

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        parent::initializeExtraProperties();
        $this->decodeAndRemoveTag();
        $this->initializeDisplayableProperties();
    }
}
