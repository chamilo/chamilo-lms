<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\App\News;

use ChamiloLMS\Controller\BaseController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class NewsController
 * @package ChamiloLMS\Controller\App\News
 * @author Julio Montoya <gugli100@gmail.com>
 */
class NewsController extends BaseController
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @return Response
     */
    public function indexAction()
    {
        if (api_is_anonymous()) {
            $visibility = \SystemAnnouncementManager::VISIBLE_GUEST;
        } else {
            $visibility = api_is_allowed_to_create_course() ? \SystemAnnouncementManager::VISIBLE_TEACHER : \SystemAnnouncementManager::VISIBLE_STUDENT;
        }
        $content = \SystemAnnouncementManager::displayAnnouncementsList($visibility, null, 'resumed');

        $this->getTemplate()->assign('content', $content);
        $response = $this->getTemplate()->renderLayout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @Route("/news/{id}")
     * @Method({"GET"})
     * @return Response
     */
    public function getNewsAction($id = null)
    {
        $actions = null;
        if (api_is_platform_admin()) {
            $actions = '<a href="'.api_get_path(WEB_PATH).'main/admin/system_announcements.php">'.\Display::return_icon('edit.png', get_lang('EditSystemAnnouncement'), array(), 32).'</a>';
        }

        if (api_is_anonymous()) {
            $visibility = \SystemAnnouncementManager::VISIBLE_GUEST;
        } else {
            $visibility = api_is_allowed_to_create_course() ? \SystemAnnouncementManager::VISIBLE_TEACHER : \SystemAnnouncementManager::VISIBLE_STUDENT;
        }

        $content =  \SystemAnnouncementManager::displayAnnouncementsList($visibility, $id, 'full');

        $this->getTemplate()->assign('content', $content);
        $this->getTemplate()->assign('actions', $actions);
        $response = $this->getTemplate()->renderLayout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }

}
