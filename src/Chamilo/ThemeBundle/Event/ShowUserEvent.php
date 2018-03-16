<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\UserInterface;

/**
 * Class ShowUserEvent.
 *
 * @package Chamilo\ThemeBundle\Event
 */
class ShowUserEvent extends ThemeEvent
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @param \Chamilo\ThemeBundle\Model\UserInterface $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \Chamilo\ThemeBundle\Model\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
