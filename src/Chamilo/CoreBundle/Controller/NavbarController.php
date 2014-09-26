<?php
/**
 * NavbarController.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\CoreBundle\Controller;

use Avanzu\AdminThemeBundle\Controller\NavbarController as AvanzuController;
use Avanzu\AdminThemeBundle\Event\MessageListEvent;
use Avanzu\AdminThemeBundle\Event\NotificationListEvent;
use Avanzu\AdminThemeBundle\Event\ShowUserEvent;
use Avanzu\AdminThemeBundle\Event\TaskListEvent;
use Avanzu\AdminThemeBundle\Event\ThemeEvents;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NavbarController
 * @package Chamilo\CoreBundle\Controller
 */
class NavbarController extends AvanzuController
{
    public function userAction()
    {
        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_NAVBAR_USER)) {
            return new Response();
        }
        $userEvent = $this->getDispatcher()->dispatch(
            ThemeEvents::THEME_NAVBAR_USER,
            new ShowUserEvent()
        );

        return $this->render(
            'ChamiloCoreBundle:Navbar:user.html.twig',
            array(
                'user' => $userEvent->getUser()
            )
        );
    }

}
