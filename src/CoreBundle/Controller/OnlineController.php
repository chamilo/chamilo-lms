<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use SocialManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[Route('/online')]
class OnlineController extends BaseController
{
    /**
     * @Route("/", name="users_online", methods={"GET"}, options={"expose"=true})
     */
    public function indexAction(): Response
    {
        // @todo don't use legacy code
        $users = who_is_online(0, MAX_ONLINE_USERS);
        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }

    /**
     * @Route("/in_course/{cidReq}", name="online_users_in_course", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function onlineUsersInCoursesAction(string $cidReq): Response
    {
        // @todo don't use legacy code
        $users = who_is_online_in_this_course(
            0,
            MAX_ONLINE_USERS,
            api_get_user_id(),
            api_get_setting('time_limit_whosonline'),
            $cidReq
        );

        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }

    /**
     * @Route("/in_sessions", name="online_users_in_session", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function onlineUsersInCoursesSessionAction(int $id = 0): Response
    {
        $users = who_is_online_in_this_course(
            0,
            MAX_ONLINE_USERS,
            api_get_user_id(),
            api_get_setting('time_limit_whosonline'),
            $_GET['cidReq']
        );

        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }
}
