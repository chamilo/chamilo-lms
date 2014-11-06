<?php
/**
 * ShowUserEvent.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\AdminThemeBundle\Event;


use Chamilo\AdminThemeBundle\Model\UserInterface;

class ShowUserEvent extends  ThemeEvent {

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @param \Chamilo\AdminThemeBundle\Model\UserInterface $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \Chamilo\AdminThemeBundle\Model\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }


}
