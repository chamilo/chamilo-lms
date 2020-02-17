<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Controller;

use Chamilo\ThemeBundle\Event\SidebarMenuKnpEvent;
use Chamilo\ThemeBundle\Event\ThemeEvents;
use Chamilo\ThemeBundle\Model\MenuItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to handle breadcrumb display inside the layout.
 */
class BreadcrumbController extends Controller
{
    /**
     * Controller Reference action to be called inside the layout.
     *
     * Triggers the {@link ThemeEvents::THEME_BREADCRUMB} to receive the currently active menu chain.
     *
     * If there are no listeners attached for this event, the return value is an empty response.
     *
     * @param string $title
     *
     * @return Response
     */
    public function breadcrumbAction(Request $request, $title = '')
    {
        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_BREADCRUMB)) {
            return new Response();
        }

        $active = $this->getDispatcher()->dispatch(
            ThemeEvents::THEME_BREADCRUMB,
            new SidebarMenuKnpEvent($request)
        )->getActive();

        /** @var $active MenuItemInterface */
        //var_dump($request->get('course'));        exit;
        //$active->addChild()

        /*$active->addChild(
            'Courses',
            array(
                'route' => 'admin_chamilo_core_course_list',
                'routeParameters' => array(),
                array("attributes" => array("id" => 'nav'))
            )
        );*/

        $list = [];
        if ($active) {
            $list[] = $active;
            while (null !== ($item = $active->getActiveChild())) {
                $list[] = $item;
                $active = $item;
            }
        }

        return $this->render('ChamiloThemeBundle:Breadcrumb:breadcrumb.html.twig', [
            'active' => $list,
            'title' => $title,
        ]);
    }

    /**
     * @return EventDispatcher
     */
    protected function getDispatcher()
    {
        return $this->get('event_dispatcher');
    }
}
