<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UserController
{
    /**
     *
     * @return Response
     */
    public function indexAction(Application $app, $username)
    {
        $userId = \UserManager::get_user_id_from_username($username);
        $content = \SocialManager::display_individual_user($userId, true);
        $app['template']->assign('content', $content);
        $response = $app['template']->render_layout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     *
     * @return Response
     */
    public function onlineAction(Application $app)
    {

        $response = $app['template']->render_layout('layout_1_col.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     *
     * @return Response
     */
    public function onlineInCourseAction(Application $app)
    {

    }

    /**
     * @param Application $app
     * @return Response
     */
    public function onlineInSessionAction(Application $app)
    {

    }



}