<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class CourseMeetingListItem extends API\MeetingListItem
{
    use CourseMeetingTrait;
    use DisplayableMeetingTrait;

    /**
     * @inheritDoc
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
