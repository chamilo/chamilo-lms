<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\UserBundle\Entity\User;
use Exception;

class UserMeetingRegistrant extends API\MeetingRegistrant
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

    /**
     * Creates a UserMeetingRegistrant instance from a user.
     *
     * @param User $user
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromUser($user)
    {
        $instance = new static();
        $instance->email = $user->getEmail();
        $instance->first_name = $user->getFirstname();
        $instance->last_name = $user->getLastname();
        $instance->userId = $user->getId();
        $instance->user = $user;
        $instance->computeFullName();

        return $instance;
    }
}
