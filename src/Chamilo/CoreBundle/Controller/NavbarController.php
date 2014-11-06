<?php
/**
 * NavbarController.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\CoreBundle\Controller;

//use Chamilo\AdminThemeBundle\Controller\NavbarController as AvanzuController;
use Chamilo\AdminThemeBundle\Event\MessageListEvent;
use Chamilo\AdminThemeBundle\Event\NotificationListEvent;
use Chamilo\AdminThemeBundle\Event\ShowUserEvent;
use Chamilo\AdminThemeBundle\Event\TaskListEvent;
use Chamilo\AdminThemeBundle\Event\ThemeEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NavbarController
 * @package Chamilo\CoreBundle\Controller
 */
class NavbarController extends Controller
{

    /**
     * @return EventDispatcher
     */
    protected function getDispatcher()
    {
        return $this->get('event_dispatcher');
    }


    public function notificationsAction($max = 5)
    {

        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_NOTIFICATIONS)) {
            return new Response();
        }

        $listEvent = $this->getDispatcher()->dispatch(ThemeEvents::THEME_NOTIFICATIONS, new NotificationListEvent());

        return $this->render(
            'ChamiloAdminTheme:Navbar:notifications.html.twig',
            array(
                'notifications' => $listEvent->getNotifications(),
                'total'         => $listEvent->getTotal()
            )
        );

    }

    public function messagesAction($max = 5)
    {

        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_MESSAGES)) {
            return new Response();
        }

        $listEvent = $this->getDispatcher()->dispatch(ThemeEvents::THEME_MESSAGES, new MessageListEvent());

        return $this->render(
            'ChamiloAdminTheme:Navbar:messages.html.twig',
            array(
                'messages' => $listEvent->getMessages(),
                'total'    => $listEvent->getTotal()
            )
        );
    }

    public function tasksAction($max = 5)
    {

        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_TASKS)) {
            return new Response();
        }
        $listEvent = $this->getDispatcher()->dispatch(ThemeEvents::THEME_TASKS, new TaskListEvent());

        return $this->render(
            'ChamiloAdminTheme:Navbar:tasks.html.twig',
            array(
                'tasks' => $listEvent->getTasks(),
                'total' => $listEvent->getTotal()
            )
        );
    }

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
