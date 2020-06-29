<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class CourseMeetingInfoGet extends API\MeetingInfoGet
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

    /**
     * Updates the meeting on server, tagging it so to remember its course and session.
     *
     * @param API\Client $client
     *
     * @throws Exception
     */
    public function update($client)
    {
        $this->tagAgenda();
        parent::update($client);
        $this->untagAgenda();
    }

    /**
     * Retrieves meeting registrants.
     *
     * @param API\Client $client
     *
     * @throws Exception
     *
     * @return UserMeetingRegistrantListItem[]
     */
    public function getUserRegistrants($client)
    {
        return UserMeetingRegistrantList::loadUserMeetingRegistrants($client, $this->id);
    }
}
