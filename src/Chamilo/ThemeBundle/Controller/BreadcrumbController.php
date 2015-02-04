<?php
/**
 * BreadcrumbController.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Controller;


use Chamilo\ThemeBundle\Event\SidebarMenuEvent;
use Chamilo\ThemeBundle\Event\ThemeEvents;
use Chamilo\ThemeBundle\Model\MenuItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to handle breadcrumb display inside the layout
 *
 */
class BreadcrumbController extends Controller {


    /**
     * Controller Reference action to be called inside the layout.
     *
     * Triggers the {@link ThemeEvents::THEME_BREADCRUMB} to receive the currently active menu chain.
     *
     * If there are no listeners attached for this event, the return value is an empty response.
     *
     * @param Request $request
     * @param string  $title
     *
     * @return Response
     *
     */
    public function breadcrumbAction(Request $request, $title = '')
    {
        if (!$this->getDispatcher()->hasListeners(ThemeEvents::THEME_BREADCRUMB)) {
            return new Response();
        }

        $active = $this->getDispatcher()->dispatch(ThemeEvents::THEME_BREADCRUMB, new SidebarMenuEvent($request))->getActive();
        /** @var $active MenuItemInterface */
        $list = array();
        if ($active) {
            $list[] = $active;
            while(null !== ($item = $active->getActiveChild())) {
                $list[] = $item;
                $active = $item;
            }
        }

        return $this->render('ChamiloThemeBundle:Breadcrumb:breadcrumb.html.twig', array(
            'active' => $list,
            'title'  => $title
        ));
    }


    /**
     * @return EventDispatcher
     */
    protected function getDispatcher()
    {
        return $this->get('event_dispatcher');
    }

}
