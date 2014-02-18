<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NewsController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class NewsController
{
    /**
     *
     * @return string
     */
    public function indexAction(Application $app, $id)
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

        $app['template']->assign('content', $content);
        $app['template']->assign('actions', $actions);
        $response = $app['template']->renderLayout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @return Response
     */
    public function newsAction(Application $app)
    {
        if (api_is_anonymous()) {
            $visibility = \SystemAnnouncementManager::VISIBLE_GUEST;
        } else {
            $visibility = api_is_allowed_to_create_course() ? \SystemAnnouncementManager::VISIBLE_TEACHER : \SystemAnnouncementManager::VISIBLE_STUDENT;
        }
        $content =  \SystemAnnouncementManager::displayAnnouncementsList($visibility, null, 'resumed');
        $app['template']->assign('content', $content);
        $response = $app['template']->renderLayout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }
}
