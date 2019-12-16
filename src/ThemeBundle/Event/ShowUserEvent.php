<?php
/**
 * ShowUserEvent.php
 * avanzu-admin
 * Date: 23.02.14.
 */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\UserInterface;

/**
 * Class ShowUserEvent.
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
